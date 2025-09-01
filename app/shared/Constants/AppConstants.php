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
    public const bool CONSOLE_ANSI = true;
    public const string HTTP_CRASH_TEMPLATE = "var/storage/defaults/crash.phtml";
}