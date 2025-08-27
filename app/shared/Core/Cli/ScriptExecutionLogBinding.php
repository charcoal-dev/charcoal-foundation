<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli;

/**
 * Class ScriptExecutionLogBinding
 * @package App\Shared\Foundation\Engine\ExecutionLog
 */
readonly class ScriptExecutionLogBinding
{
    public function __construct(
        public bool    $loggable = true,
        public ?string $label = null,
        public bool    $outputBuffering = false,
    )
    {
    }
}