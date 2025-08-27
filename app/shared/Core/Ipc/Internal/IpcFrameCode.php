<?php
declare(strict_types=1);

namespace App\Shared\Core\Ipc\Internal;

/**
 * Class AppIpcFrameCode
 * @package App\Shared\Core\Ipc
 */
enum IpcFrameCode: int
{
    case PING = 1;

    case CLI_EXEC_STATE = 2001;
}