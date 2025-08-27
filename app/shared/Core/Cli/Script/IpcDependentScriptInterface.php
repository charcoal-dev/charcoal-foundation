<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Script;

use App\Shared\Context\IpcService;

/**
 * Interface IpcDependentScriptInterface
 * @package App\Shared\Core\Cli\Script
 */
interface IpcDependentScriptInterface
{
    public function ipcDependsOn(): IpcService;

    public function waitForIpcService(
        IpcService $dependsOn,
        string     $whoAmI,
        int        $interval = 3,
        int        $maxAttempts = 300
    ): void;
}