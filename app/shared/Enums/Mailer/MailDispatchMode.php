<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums\Mailer;

/**
 * This enum defines the available options for handling mail dispatch operations,
 * specifying whether mail should be queued, sent immediately, or both.
 */
enum MailDispatchMode: string
{
    case QUEUE_ONLY = "queue_only";
    case SEND_AND_QUEUE = "send_and_queue";
    case SEND_ONLY = "send_only";
}