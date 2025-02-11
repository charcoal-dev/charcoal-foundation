<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Config;

/**
 * Class MailDispatchMode
 * @package App\Shared\Foundation\Mailer\Config
 */
enum MailDispatchMode: string
{
    case QUEUE_ONLY = "queue_only";
    case SEND_AND_QUEUE = "send_and_queue";
    case SEND_ONLY = "send_only";
}