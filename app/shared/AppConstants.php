<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

use Charcoal\Contracts\Errors\ExceptionAction;

/**
 * Defines application-wide constants used for configuration or file paths.
 */
interface AppConstants
{
    /** @for=Error Handling */
    public const bool CONSOLE_ANSI = true;
    public const string HTTP_CRASH_TEMPLATE = "var/storage/defaults/crash.phtml";

    /** @for=ORM */
    public const ExceptionAction ORM_CACHE_ERROR_HANDLING = ExceptionAction::Throw;

    /** @for=Security */
    /** @var string Directory path or ref for default secrets namespace inside Local store */
    public const string SECRETS_LOCAL_NAMESPACE = "charcoal";
}