<?php
declare(strict_types=1);

namespace App\Shared\Core\Ipc;

/**
 * Class IpcMessage
 * @package App\Shared\Core\Cli\Ipc
 */
readonly class IpcMessage
{
    public function __construct(
        public string $message,
        public string $address,
    )
    {
    }
}