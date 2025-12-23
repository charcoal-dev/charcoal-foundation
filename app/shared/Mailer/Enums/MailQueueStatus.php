<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Mailer\Enums;

use Charcoal\Base\Enums\Traits\EnumMappingTrait;

/**
 * This enumeration defines the possible states a mail item can have
 * while being processed in the mail queue.
 */
enum MailQueueStatus: string
{
    case Queued = "queued";
    case Sent = "sent";
    case Error = "error";
    case Exhausted = "exhausted";

    use EnumMappingTrait;
}