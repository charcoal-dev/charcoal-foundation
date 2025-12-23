<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

/**
 * The enum includes constants for foundational services such as
 * storage, communication, and utility systems, as well as placeholder values.
 */
enum AppBindings
{
    /** @for Foundation App */
    case coreData;
    case telemetry;
    case mailer;

    /** @for Placeholder */
    case ethereal;
}