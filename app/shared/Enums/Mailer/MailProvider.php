<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums\Mailer;

/**
 * Defines the available mail delivery providers.
 */
enum MailProvider: string
{
    case DISABLED = "disabled";
    case SMTP = "smtp";
}