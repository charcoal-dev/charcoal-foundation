<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Ipc;

use App\Shared\Context\IpcService;
use App\Shared\Core\Ipc\IpcSocket;

/**
 * Interface IpcServerInterface
 * @package App\Shared\Core\Cli\Ipc
 */
interface IpcServerInterface
{
    public function ipcServerOnConstructHook(): void;

    public function ipcSocket(): IpcSocket;

    public function ipcServiceEnum(): IpcService;
}