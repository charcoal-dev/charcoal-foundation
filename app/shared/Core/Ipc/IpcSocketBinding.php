<?php
declare(strict_types=1);

namespace App\Shared\Core\Ipc;

/**
 * Class IpcSocketBinding
 * @package App\Shared\Core\Cli\Ipc
 */
readonly class IpcSocketBinding
{
    public function __construct(
        public string $socketFile,
        public int    $dataGramSize = 1024
    )
    {
    }
}