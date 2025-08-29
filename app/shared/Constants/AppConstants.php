<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Constants;

/**
 * Defines application-wide constants used for configuration or file paths.
 */
interface AppConstants
{
    public const string ERROR_SINK = "logs/error.log";
    public const string CRASH_HTML_TEMPLATE = "storage/defaults/crash.phtml";
}