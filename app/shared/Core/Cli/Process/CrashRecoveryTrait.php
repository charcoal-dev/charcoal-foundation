<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Process;

use App\Shared\Core\Cli\AppAwareCliProcess;
use App\Shared\Core\Cli\CliScriptState;

/**
 * Trait RecoverableCliProcessTrait
 * @package App\Shared\Core\Cli
 * @mixin AppAwareCliProcess
 */
trait CrashRecoveryTrait
{
    protected readonly ?RecoverableProcessBinding $recovery;

    abstract protected function declareRecoverableProcess(): ?RecoverableProcessBinding;

    /**
     * @return void
     */
    public function recoveryOnConstructHook(): void
    {
        $this->recovery = $this->declareRecoverableProcess();
    }

    /**
     * @return bool
     */
    public function isRecoverable(): bool
    {
        if (isset($this->recovery)) {
            if ($this->recovery->recoverable &&
                $this->recovery->ticks > 0 &&
                $this->recovery->ticksInterval > 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return void
     */
    protected function beforeHealingStart(): void
    {
        $this->memoryCleanup(dbQueries: true, errorLog: true, runtimeObjects: true, verbose: false);
    }

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    protected function onHealingFinished(): void
    {
        $this->cli->catchPcntlSignal();

        $this->print("{cyan}~~~~~");
        $this->print("{cyan}Restating App Process...");
        if ($this->logger) {
            $this->logger->context->log("App Process Restarted");
            $this->logger->changeState(CliScriptState::STARTED);
            $this->logger->saveStateContext()->captureCpuStats(upsertState: false);
        }

        $this->print("");
    }

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    final protected function handleRecoveryAfterCrash(): void
    {
        $this->print("");
        $this->inline(sprintf("{grey}Recovery in {b}%d{/} ticks ", $this->recovery->ticks));
        $recoveryEta = round(($this->recovery->ticks * $this->recovery->ticksInterval) / $this->recovery->ticksInterval, 1);
        if ($this->logger) {
            $this->logger->context->log(sprintf("Recovery expected in %s seconds", $recoveryEta));
            $this->logger->changeState(CliScriptState::HEALING);
            $this->logger->saveStateContext()->captureCpuStats(upsertState: false);
        }

        $this->beforeHealingStart();

        for ($i = 0; $i < $this->recovery->ticks; $i++) {
            if (($i % 3) === 0) { // On every 3rd tick
                $this->cli->catchPcntlSignal();
            }

            usleep($this->recovery->ticksInterval);
            $this->inline(".");
        }

        $this->print("")->print("");
        $this->onHealingFinished();
    }
}