<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli;

use Charcoal\OOP\Traits\EnumOptionsTrait;

/**
 * Class CliScriptState
 * @package App\Shared\Core\Cli
 */
enum CliScriptState: string
{
    case STARTED = "started";
    case READY = "ready";
    case RUNNING = "running";
    case PAUSED = "paused";
    case ERROR = "error";
    case HEALING = "healing";
    case STOPPED = "stopped";
    case FINISHED = "finished";
    case UNKNOWN = "unknown";

    use EnumOptionsTrait;
}

