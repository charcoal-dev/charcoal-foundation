<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use App\Shared\CharcoalApp;
use Charcoal\Mailer\Contracts\MailProviderInterface;

/**
 * Represents different types of mail providers available for use.
 */
enum MailProvider: string
{
    case Disabled = "disabled";
    case Smtp = "smtp";

    /**
     * @param CharcoalApp $app
     * @return MailProviderInterface|null
     */
    public function getAgent(CharcoalApp $app): ?MailProviderInterface
    {
        return null;
    }
}