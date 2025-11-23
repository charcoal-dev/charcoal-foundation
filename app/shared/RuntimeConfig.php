<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

use Charcoal\App\Kernel\Enums\LogLevel;

/**
 * Represents the runtime configuration settings for CharcoalApp.
 */
final class RuntimeConfig
{
    public bool $logAppLogs = false;
    public LogLevel $appLogLevel = LogLevel::Info;
    public bool $logHttpIngress = false;
}