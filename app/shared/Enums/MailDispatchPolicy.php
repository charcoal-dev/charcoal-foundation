<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Represents the policies available for handling mail dispatch within the system.
 * Each case corresponds to a specific mode of operation for dispatching emails.
 */
enum MailDispatchPolicy: string
{
    case Queue_Only = "queue_only";
    case Send_Only = "send_only";
    case Send_And_Log = "send_and_log";
}