<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli;

/**
 * Trait RecoverableCliProcessTrait
 * @package App\Shared\Core\Cli
 * @mixin AppAwareCliProcess
 */
trait RecoverableCliProcessTrait
{
    protected readonly ?RecoverableProcessBinding $recovery;

    abstract protected function declareRecoverableProcess(): ?RecoverableProcessBinding;

    /**
     * @return void
     */
    protected function initRecoverableProcess(): void
    {
        $this->recovery = $this->declareRecoverableProcess();
    }

    protected function beforeCrashRecovery(): void
    {
        $this->cleanUpMemory(dbQueries: true, errorLog: true, runtimeObjects: true, verbose: false);
    }

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    protected function onCrashRecovery(): void
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
    final protected function handleProcessRecovery(): void
    {
        $this->print("");
        $this->inline(sprintf("{grey}Recovery in {b}%d{/} ticks ", $this->recovery->ticks));
        $recoveryEta = round(($this->recovery->ticks * $this->recovery->ticksInterval) / $this->recovery->ticksInterval, 1);
        if ($this->logger) {
            $this->logger->context->log(sprintf("Recovery expected in %s seconds", $recoveryEta));
            $this->logger->changeState(CliScriptState::HEALING);
            $this->logger->saveStateContext()->captureCpuStats(upsertState: false);
        }

        $this->beforeCrashRecovery();

        for ($i = 0; $i < $this->recovery->ticks; $i++) {
            if (($i % 3) === 0) { // On every 3rd tick
                $this->cli->catchPcntlSignal();
            }

            usleep($this->recovery->ticksInterval);
            $this->inline(".");
        }

        $this->print("")->print("");
        $this->onCrashRecovery();
    }
}