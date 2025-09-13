<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Sapi\Cli;

/**
 * Class ScriptExecutionLogBinding
 * @package App\Shared\Foundation\Engine\ExecutionLog
 */
readonly class LogPolicy
{
    public function __construct(
        public bool    $loggable = true,
        public ?string $label = null,
        public bool    $outputBuffering = false,
    )
    {
    }
}