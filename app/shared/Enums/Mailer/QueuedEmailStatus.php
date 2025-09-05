<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums\Mailer;

use Charcoal\Base\Enums\Traits\EnumMappingTrait;

/**
 * Represents the various statuses an email can have during its lifecycle in the queue.
 */
enum QueuedEmailStatus: string
{
    case PENDING = "pending";
    case SENT = "sent";
    case RETRYING = "retrying";
    case FAILED = "failed";
    case CANCELLED = "cancelled";

    use EnumMappingTrait;
}