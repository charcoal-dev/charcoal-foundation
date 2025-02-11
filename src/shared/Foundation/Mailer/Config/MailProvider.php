<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Config;

/**
 * Class MailProvider
 * @package App\Shared\Foundation\Mailer\Config
 */
enum MailProvider: string
{
    case DISABLED = "disabled";
    case SMTP = "smtp";
}