<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Cli;

use App\Shared\CharcoalApp;
use App\Shared\Enums\SemaphoreScopes;
use App\Shared\Exceptions\CliScriptException;
use App\Shared\Utility\TypeCaster;
use Charcoal\App\Kernel\EntryPoint\Cli\AppCliHandler;
use Charcoal\App\Kernel\EntryPoint\Cli\AppCliScript;
use Charcoal\App\Kernel\Enums\SemaphoreType;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Cli\Events\Terminate\ExceptionCaught;
use Charcoal\Cli\Events\Terminate\PcntlSignalClose;
use Charcoal\Semaphore\Exceptions\SemaphoreLockException;
use Charcoal\Semaphore\Filesystem\FileLock;

/**
 * Class DomainScriptBase
 * @package App\Shared\Core\Cli
 */
abstract class DomainScriptBase extends AppCliScript
{
    public readonly int $startedOn;
    protected readonly ?LogPolicy $logBinding;
    protected readonly ?ScriptLogger $logger;
    protected readonly ?FileLock $semaphoreLock;

    /**
     * @param AppCliHandler $cli
     * @param ExecutionState $initialState
     * @param string|null $semaphoreLockId
     * @throws SemaphoreLockException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreUnlockException
     */
    public function __construct(
        AppCliHandler              $cli,
        ExecutionState             $initialState = ExecutionState::STARTED,
        protected readonly ?string $semaphoreLockId = null
    )
    {
        parent::__construct($cli);
        $this->startedOn = time();
        $this->state = $initialState;

        $this->onConstructHook();

        $this->semaphoreLock = $this->semaphoreLockId ?
            $this->obtainSemaphoreLock($this->semaphoreLockId, true) : null;

        // Declared Depends On?
        /*if ($this instanceof IpcDependentScriptInterface) {
            $this->waitForIpcService(
                $this->ipcDependsOn(),
                $this->semaphoreLockId ?? $this->scriptClassname,
                interval: 3,
                maxAttempts: 100
            );
        }*/

        // Log Binding & ScriptExecutionLogger
        $this->logBinding = $this->declareExecutionLogging();
        if (!$this->logBinding->loggable) {
            $this->logger = null;
        } else {
            $this->logger = new ScriptLogger(
                $this,
                $this->state,
                true,
                $this->logBinding->label,
                getmypid(),
            );

            $this->print("{green}Started process tracking # {b}" . $this->logger->logId);

            $this->cli->events->subscribe()->listen(ExceptionCaught::class, function () {
                if ($this->logger) {
                    $this->closeScriptLogger($this->logger, ExecutionState::ERROR);
                }
            });

            $this->cli->events->subscribe()->listen(PcntlSignalClose::class, function () {
                if ($this->logger) {
                    $this->closeScriptLogger($this->logger, ExecutionState::ERROR);
                }
            });
        }
    }

    /**
     * @return LogPolicy
     */
    abstract protected function declareExecutionLogging(): LogPolicy;

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
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
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
                $this->closeScriptLogger($this->logger, ExecutionState::FINISHED);
            }
        } catch (\Throwable $t) {
            if (isset($this->logBinding, $this->logger)) {
                $this->logger->context->logException($t);
                $this->closeScriptLogger($this->logger, ExecutionState::ERROR);
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
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->cli->app;
    }

    /**
     * @return ExecutionState
     * @api
     */
    public function getState(): ExecutionState
    {
        return $this->state;
    }

    /**
     * @param ExecutionState $newState
     * @return void
     * @api
     */
    public function changeState(ExecutionState $newState): void
    {
        $this->state = $newState;
        $this->logger?->changeState($newState);
    }

    /**
     * @param string $label
     * @param callable $childFn
     * @param array $childArgs
     * @param bool $scriptAwareContext
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @api
     */
    protected function childExecutionLog(
        string   $label,
        callable $childFn,
        array    $childArgs = [],
        bool     $scriptAwareContext = false
    ): void
    {
        $execLogger = new ScriptLogger(
            $this,
            ExecutionState::STARTED,
            $scriptAwareContext,
            $label,
            getmypid()
        );

        $this->print("{cyan}Started Child Execution Logger # {yellow}" . $execLogger->logId);
        $this->print("{green}" . $label);

        try {
            call_user_func_array($childFn, [$execLogger, ...$childArgs]);
            $this->closeScriptLogger($execLogger, ExecutionState::FINISHED);
        } catch (\Throwable $t) {
            $execLogger->context->logException($t);
            $this->closeScriptLogger($execLogger, ExecutionState::ERROR);
            throw $t;
        } finally {
            unset($execLogger);
        }
    }

    /**
     * @param int $tabs
     * @param bool $compact
     * @return void
     * @api
     */
    protected function printErrorsIfAny(int $tabs = 0, bool $compact = true): void
    {
        $this->cli->printErrors($tabs, $compact);
    }

    /**
     * @return string
     * @api
     */
    public function getTraceInterface(): string
    {
        return "cli";
    }

    /**
     * @return int|null
     * @api
     */
    public function getTraceId(): ?int
    {
        return $this->logger?->logId;
    }

    /**
     * @param ScriptLogger $logger
     * @param ExecutionState $finalState
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    private function closeScriptLogger(ScriptLogger $logger, ExecutionState $finalState): void
    {
        if ($logger->getState() !== ExecutionState::STARTED) {
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
     * @throws \Charcoal\Semaphore\Exceptions\SemaphoreUnlockException
     */
    protected function obtainSemaphoreLock(string $resourceId, bool $setAutoRelease): FileLock
    {
        $this->inline(sprintf("Obtaining semaphore lock for {yellow}{invert} %s {/} ... ", $resourceId));

        try {
            $lock = $this->getAppBuild()->security
                ->semaphore(SemaphoreType::Filesystem_Private)
                ->lock(SemaphoreScopes::Cli, $resourceId);

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
}