<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Backlog;

use Charcoal\OOP\Traits\EnumOptionsTrait;

/**
 * Class QueuedEmailStatus
 * @package App\Shared\Foundation\Mailer\Backlog
 */
enum QueuedEmailStatus: string
{
    case PENDING = "pending";
    case SENT = "sent";
    case RETRYING = "retrying";
    case FAILED = "failed";
    case CANCELLED = "cancelled";

    use EnumOptionsTrait;
}