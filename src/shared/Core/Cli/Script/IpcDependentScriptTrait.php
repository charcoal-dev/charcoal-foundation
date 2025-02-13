<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Script;

use App\Shared\Core\Cli\Ipc\IpcClientTrait;
use App\Shared\Core\Ipc\IpcService;

/**
 * Trait IpcDependentProcessTrait
 * @package App\Shared\Core\Cli\Script
 */
trait IpcDependentScriptTrait
{
    use IpcClientTrait;

    abstract public function ipcDependsOn(): IpcService;

    /**
     * @param IpcService $dependsOn
     * @param string $whoAmI
     * @param int $interval
     * @param int $maxAttempts
     * @return void
     */
    public function waitForIpcService(
        IpcService $dependsOn,
        string     $whoAmI,
        int        $interval = 3,
        int        $maxAttempts = 300
    ): void
    {
        $this->print("");
        $this->inline("Waiting for {invert}{yellow} " . $dependsOn->name . " {/} service {grey}");

        $serviceOnline = false;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $this->inline(".");

            try {
                $this->ipcSendCurrentState($dependsOn, $whoAmI);
                $serviceOnline = true;
                break;
            } catch (\Exception) {
            }

            sleep($interval);
            $this->cli->catchPcntlSignal();
        }

        if ($serviceOnline) {
            $this->print(" {green}Success");
            return;
        }

        $this->print("");
        throw new \RuntimeException('Failed to connect with "' . $dependsOn->name . '"');
    }
}