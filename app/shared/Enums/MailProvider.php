<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Represents different types of mail providers available for use.
 */
enum MailProvider: string
{
    case Disabled = "disabled";
    case Smtp = "smtp";
}