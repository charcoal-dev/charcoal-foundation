<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\AppLogs;

use Charcoal\App\Kernel\Enums\LogLevel;
use Charcoal\Base\Enums\Traits\EnumMappingTrait;

/**
 * Represents the application log levels, mapped to specific string values.
 * This enum is used to define different logging levels that can be used within an application.
 */
enum AppLogLevel: string
{
    case Verbose = "verbose";
    case Debug = "debug";
    case Info = "info";
    case Notice = "notice";
    case Warning = "warning";
    case Error = "error";
    case Critical = "critical";
    case Unknown = "unknown";

    use EnumMappingTrait;

    /**
     * Converts a LogLevel instance to the corresponding diagnostic log level enumeration.
     */
    public static function fromDiagnostics(LogLevel $level)
    {
        return self::tryFrom(strtolower($level->name)) ?? self::Unknown;
    }
}