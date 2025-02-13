<?php
declare(strict_types=1);

namespace App\Shared\Core\Ipc;

use App\Shared\Core\Directories;

/**
 * Class IpcService
 * @package App\Shared\Core\Ipc
 */
enum IpcService: string
{
    case APP_DAEMON = "./app_daemon.ipc";

    /**
     * @param Directories $directories
     * @return IpcSocketBinding
     */
    public function getBinding(Directories $directories): IpcSocketBinding
    {
        return new IpcSocketBinding($directories->tmp->pathToChild($this->value), 1024);
    }
}