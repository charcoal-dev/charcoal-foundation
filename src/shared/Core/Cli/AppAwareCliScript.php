<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli;

use App\Shared\CharcoalApp;
use App\Shared\Core\Cli\Script\IpcDependentScriptInterface;
use App\Shared\Exception\CliScriptException;
use App\Shared\Foundation\CoreData\SystemAlerts\AlertTraceProviderInterface;
use App\Shared\Utility\TypeCaster;
use Charcoal\App\Kernel\Interfaces\Cli\AbstractCliScript;
use Charcoal\App\Kernel\Interfaces\Cli\AppCliHandler;
use Charcoal\Semaphore\Exception\SemaphoreLockException;
use Charcoal\Semaphore\Filesystem\FileLock;

/**
 * Class AppAwareCliScript
 * @package App\Shared\Core\Cli
 */
abstract class AppAwareCliScript extends AbstractCliScript implements AlertTraceProviderInterface
{
    public readonly int $startedOn;
    protected readonly ?ScriptExecutionLogBinding $logBinding;
    protected readonly ?ScriptExecutionLogger $logger;
    protected readonly ?FileLock $semaphoreLock;
    private CliScriptState $state;

    /**
     * @param AppCliHandler $cli
     * @param CliScriptState $initialState
     * @param string|null $semaphoreLockId
     * @throws SemaphoreLockException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(
        AppCliHandler              $cli,
        protected readonly ?string $semaphoreLockId = null,
        CliScriptState             $initialState = CliScriptState::STARTED
    )
    {
        parent::__construct($cli);
        $this->startedOn = time();
        $this->state = $initialState;

        $this->onConstructHook();

        $this->semaphoreLock = $this->semaphoreLockId ?
            $this->obtainSemaphoreLock($this->semaphoreLockId, true) : null;

        // Declared Depends On?
        if ($this instanceof IpcDependentScriptInterface) {
            $this->waitForIpcService(
                $this->ipcDependsOn(),
                $this->semaphoreLockId ?? $this->scriptClassname,
                interval: 3,
                maxAttempts: 100
            );
        }

        // Log Binding & ScriptExecutionLogger
        $this->logBinding = $this->declareExecutionLogging();
        if (!$this->logBinding->loggable) {
            $this->logger = null;
        } else {
            $this->logger = new ScriptExecutionLogger(
                $this,
                $this->state,
                true,
                $this->logBinding->label,
                getmypid(),
                $this->logBinding->outputBuffering,
                true
            );

            $this->print("{green}Started process tracking # {b}" . $this->logger->logId);

            $this->cli->events->scriptExecException()->listen(function () {
                if ($this->logger) {
                    $this->closeScriptLogger($this->logger, CliScriptState::ERROR);
                }
            });

            $this->cli->events->pcntlSignalClose()->listen(function (int $sigId) {
                $this->logger->context->log("PCNTL Signal #$sigId received");
                if ($this->logger) {
                    $this->closeScriptLogger($this->logger, CliScriptState::STOPPED);
                }
            });
        }
    }

    /**
     * @return ScriptExecutionLogBinding
     */
    abstract protected function declareExecutionLogging(): ScriptExecutionLogBinding;

    /**
     * @return void
     */
    abstract protected function onConstructHook(): void;

    /**
     * @return void
     */
    abstract protected function execScript(): void;

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Throwable
     */
    final public function exec(): void
    {
        if ($this->timeLimit <= 0 && TypeCaster::toBool($this->cli->args->get("tty")) === false) {
            extension_loaded("pcntl") ? pcntl_alarm(30) :
                throw new \RuntimeException('Cannot execute script with no time limit outside an interactive terminal');
        }

        try {
            $this->execScript();
            if (isset($this->logger)) {
                $this->closeScriptLogger($this->logger, CliScriptState::FINISHED);
            }
        } catch (\Throwable $t) {
            if (isset($this->logBinding, $this->logger)) {
                $this->logger->context->logException($t);
                $this->closeScriptLogger($this->logger, CliScriptState::ERROR);
            }

            if ($t instanceof CliScriptException) {
                $this->eol()->print("{red}" . $t->getMessage());
                if ($t->getPrevious()) {
                    throw $t->getPrevious();
                }

                return;
            }

            throw $t;
        }
    }

    /**
     * @return CharcoalApp
     */
    public function getAppBuild(): CharcoalApp
    {
        return $this->cli->app;
    }

    /**
     * @return CliScriptState
     */
    public function getState(): CliScriptState
    {
        return $this->state;
    }

    /**
     * @param CliScriptState $newState
     * @return void
     */
    public function changeState(CliScriptState $newState): void
    {
        $this->state = $newState;
        $this->logger?->changeState($newState);
    }

    /**
     * @param string $label
     * @param bool $outputBuffering
     * @param callable $childFn
     * @param array $childArgs
     * @param bool $scriptAwareContext
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    protected function childExecutionLog(
        string   $label,
        bool     $outputBuffering,
        callable $childFn,
        array    $childArgs = [],
        bool     $scriptAwareContext = false
    ): void
    {
        $execLogger = new ScriptExecutionLogger(
            $this,
            CliScriptState::STARTED,
            $scriptAwareContext,
            $label,
            getmypid(),
            $outputBuffering,
            truncateOutputBufferFile: true
        );

        $this->print("{cyan}Started Child Execution Logger # {yellow}" . $execLogger->logId);
        $this->print("{green}" . $label);

        try {
            call_user_func_array($childFn, [$execLogger, ...$childArgs]);
            $this->closeScriptLogger($execLogger, CliScriptState::FINISHED);
        } catch (\Throwable $t) {
            $execLogger->context->logException($t);
            $this->closeScriptLogger($execLogger, CliScriptState::ERROR);
            throw $t;
        } finally {
            unset($execLogger);
        }
    }

    /**
     * @param int $tabs
     * @param bool $compact
     * @return void
     */
    protected function printErrorsIfAny(int $tabs = 0, bool $compact = true): void
    {
        if ($this->getAppBuild()->errors->count()) {
            $this->cli->printErrors($tabs, $compact);
        }
    }

    /**
     * @return string
     */
    public function getTraceInterface(): string
    {
        return "cli";
    }

    /**
     * @return int|null
     */
    public function getTraceId(): ?int
    {
        return $this->logger?->logId;
    }

    /**
     * @param ScriptExecutionLogger $logger
     * @param CliScriptState $finalState
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    private function closeScriptLogger(ScriptExecutionLogger $logger, CliScriptState $finalState): void
    {
        if ($logger->getState() !== CliScriptState::STARTED) {
            $logger->changeState($finalState);
        }

        $logger->saveStateContext()->captureCpuStats(upsertState: true);
        $logger->close();
    }

    /**
     * @param string $resourceId
     * @param bool $setAutoRelease
     * @return FileLock
     * @throws SemaphoreLockException
     */
    protected function obtainSemaphoreLock(string $resourceId, bool $setAutoRelease): FileLock
    {
        $this->inline(sprintf("Obtaining semaphore lock for {yellow}{invert} %s {/} ... ", $resourceId));

        try {
            $lock = $this->getAppBuild()->semaphore->obtainLock($resourceId, null);
            $this->inline("{green}Success{/} {grey}[AutoRelease={/}");
            if ($setAutoRelease) {
                $lock->setAutoRelease();
                $this->print("{green}1{grey}]{/}");
            } else {
                $this->print("{red}0{grey}]{/}");
            }

            return $lock;
        } catch (SemaphoreLockException $e) {
            $this->print("{red}{invert} " . $e->error->name . " {/}");
            throw $e;
        }
    }

    /**
     * A safe sleep method that can catch PCNTL signals while sleeping
     * @param int $seconds
     * @return void
     */
    protected function safeSleep(int $seconds = 1): void
    {
        for ($i = 0; $i < $seconds; $i++) {
            if (($i % 3) === 0) {
                $this->cli->catchPcntlSignal();
            }

            sleep(1);
        }
    }
}