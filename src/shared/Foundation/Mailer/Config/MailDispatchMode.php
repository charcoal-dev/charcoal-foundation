<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Config;

/**
 * Class MailDispatchMode
 * @package App\Shared\Foundation\Mailer\Config
 */
enum MailDispatchMode
{
    case QUEUE_ONLY;
    case SEND_AND_QUEUE;
    case SEND_ONLY;
}