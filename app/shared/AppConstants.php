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
    /** @var int Number of iterations to perform when remixing secret keys (from app's Primary key)') */
    public const int SECRETS_REMIX_ITERATIONS = 1;

    /** @for=Mailer */
    /** @var string Mailer signatures used in MIME formated messages */
    public const string MAILER_SIGNATURE = "Charcoal Foundation v0.2.x";
    public const string MAILER_BOUNDARY_PREFIX = "--Charcoal_B";
}