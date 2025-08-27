<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums\Http;

/**
 * Represents different policies for handling Cross-Origin Resource Sharing (CORS).
 */
enum CorsPolicy
{
    case DISABLED;
    case ALLOW_ALL;
    case ENFORCE_ORIGIN;
}