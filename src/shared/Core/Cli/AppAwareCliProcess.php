<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli;

use App\Shared\Exception\CliForceTerminateException;
use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertContext;
use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertLevel;
use Charcoal\App\Kernel\Interfaces\Cli\AppCliHandler;
use Charcoal\Semaphore\Filesystem\FileLock;

/**
 * Class AppAwareCliProcess
 * @package App\Shared\Core\Cli
 * @mixin RecoverableCliProcessTrait
 */
abstract class AppAwareCliProcess extends AppAwareCliScript
{
    final protected const int TIME_LIMIT = 0;

    protected ?FileLock $semaphoreLock = null;

    public function __construct(
        AppCliHandler               $cli,
        protected readonly ?string  $semaphoreLockId,
        protected ?SystemAlertLevel $crashAlertLevel = null,
    )
    {

    }

    protected function onConstructHook(): void
    {
        // IPC Handling?
    }

    abstract protected function onEachTick(): void;

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Throwable
     */
    final function execScript(): void
    {
        while (true) {
            try {
                $this->onEachTick();
                $this->cli->onEveryLoop();
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
        if ($this->crashAlertLevel) {
            if (isset($this->getAppBuild()->coreData->alerts)) {
                $systemAlert = $this->getAppBuild()->coreData->alerts->raise(
                    $this->crashAlertLevel,
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

        // Recovery?
        if (!$this->checkIsRecoverable($t)) {
            throw $t;
        }

        $this->logger?->context->logException($t);
        $this->handleProcessRecovery();
    }

    /**
     * @param \Throwable $t
     * @return bool
     */
    private function checkIsRecoverable(\Throwable $t): bool
    {
        if ($t instanceof CliForceTerminateException) {
            return false;
        }

        if (in_array(RecoverableCliProcessTrait::class, class_uses($this), true)) {
            if ($this->recovery) {
                if ($this->recovery->recoverable &&
                    $this->recovery->ticks > 0 &&
                    $this->recovery->ticksInterval > 0) {
                    return true;
                }
            }
        }

        return false;
    }
}