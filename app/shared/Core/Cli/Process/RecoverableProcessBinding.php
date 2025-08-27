<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Process;

/**
 * Class RecoverableProcessBinding
 * @package App\Shared\Core\Cli\Process
 */
readonly class RecoverableProcessBinding
{
    public function __construct(
        public bool $recoverable,
        public int  $ticks = 300,
        public int  $ticksInterval = 1000000,
    )
    {
    }
}