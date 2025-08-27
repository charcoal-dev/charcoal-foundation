<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli;

use App\Shared\Core\Cli\Ipc\IpcServerInterface;
use App\Shared\Core\Cli\Process\CrashRecoverableProcessInterface;
use App\Shared\Core\Cli\Process\CrashSystemAlertInterface;
use App\Shared\Exception\CliForceTerminateException;
use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertContext;
use Charcoal\App\Kernel\Interfaces\Cli\AppCliHandler;
use Charcoal\App\Kernel\Module\CacheAwareModule;
use Charcoal\OOP\OOP;

/**
 * Class AppAwareCliProcess
 * @package App\Shared\Core\Cli
 */
abstract class AppAwareCliProcess extends AppAwareCliScript
{
    final protected const int TIME_LIMIT = 0;

    /**
     * @param AppCliHandler $cli
     * @param string|null $semaphoreLockId
     * @param CliScriptState $initialState
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Semaphore\Exception\SemaphoreLockException
     */
    public function __construct(
        AppCliHandler  $cli,
        ?string        $semaphoreLockId,
        CliScriptState $initialState = CliScriptState::STARTED,
    )
    {
        parent::__construct($cli, $semaphoreLockId, $initialState);
    }

    /**
     * @return void
     */
    protected function onConstructHook(): void
    {
        if ($this instanceof CrashRecoverableProcessInterface) {
            $this->recoveryOnConstructHook();
        }

        if ($this instanceof IpcServerInterface) {
            $this->ipcServerOnConstructHook();
        }
    }

    /**
     * Execution logic for every tick, return number of seconds to sleep until next interval
     * @return int
     */
    abstract protected function onEachTick(): int;

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Throwable
     */
    final function execScript(): void
    {
        while (true) {
            try {
                $interval = $this->onEachTick();
                $this->cli->onEveryLoop();
                $this->safeSleep(max($interval, 1));
            } catch (\Throwable $t) {
                $this->handleProcessCrash($t);
            }
        }
    }

    /**
     * @param \Throwable $t
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Throwable
     */
    protected function handleProcessCrash(\Throwable $t): void
    {
        $this->print("")->print("{red}{b}Process has crashed!")
            ->print(sprintf("{red}[{yellow}%s{/}{red}]: %s{/}", get_class($t), $t->getMessage()));

        $this->logger?->context->log("Process crashed with exception: " . get_class($t));

        // CrashSystemAlertInterface
        if ($this instanceof CrashSystemAlertInterface) {
            if (isset($this->getAppBuild()->coreData->alerts)) {
                $systemAlert = $this->getAppBuild()->coreData->alerts->raise(
                    $this->alertLevelOnCrash(),
                    sprintf('App process "%s" crashed with "%s"', $this->semaphoreLockId, get_class($t)),
                    new SystemAlertContext($t),
                    $this,
                    true
                );

                $this->print(sprintf("\t{yellow}System Alert Raised! ({green}%d{/}{yellow})", $systemAlert->id));
                $this->logger?->context->log("System alert raised: " . $systemAlert->id);
                unset($sysAlert);
            }
        }

        // Check for recovery options after crash...
        if ($t instanceof CliForceTerminateException) {
            throw $t;
        }

        // CrashRecoverableProcessInterface
        $recoverable = false;
        if ($this instanceof CrashRecoverableProcessInterface) {
            if ($this->isRecoverable()) {
                $recoverable = true;
            }
        }

        if (!$recoverable) {
            throw $t;
        }

        $this->logger?->context->logException($t);
        $this->handleRecoveryAfterCrash();
    }

    /**
     * @param bool $dbQueries
     * @param bool $errorLog
     * @param bool $runtimeObjects
     * @param bool $verbose
     * @return void
     */
    protected function memoryCleanup(bool $dbQueries, bool $errorLog, bool $runtimeObjects, bool $verbose = true): void
    {
        call_user_func([$this, $verbose ? "print" : "inline"], "{cyan}Runtime memory clean-up initiated: ");
        $app = $this->getAppBuild();

        // Lifecycle Logs
        $app->lifecycle->purgeAll();

        // Database queries
        if ($dbQueries) {
            if ($verbose) $this->inline("\t[{green}*{/}] Database queries: ");
            $app->databases->flushAllQueries();
            if ($verbose) $this->print("{green}Done");
        }

        // Error log
        if ($errorLog) {
            if ($verbose) $this->inline("\t[{green}*{/}] Error handler log: ");
            $app->errors->flush();
            if ($verbose) $this->print("{green}Done");
        }

        // Run-time objects memory
        if ($runtimeObjects) {
            if ($verbose) $this->print("\t[{green}@{/}] App ORM Modules: ");
            foreach ($app->build->modulesProperties as $moduleProperty) {
                if (isset($app->$moduleProperty)) {
                    $moduleInstance = $app->$moduleProperty;
                    if ($moduleInstance instanceof CacheAwareModule) {
                        if ($verbose) $this->inline("\t\t[{green}*{/}] {yellow}" . OOP::baseClassName($moduleInstance::class) . "{/}: ");
                        $moduleInstance->memoryCache->purgeRuntimeMemory();
                        if ($verbose) $this->print("{green}Done");
                    }
                }
            }
        }

        if (!$verbose) {
            $this->print("{green}Done");
        }
    }
}