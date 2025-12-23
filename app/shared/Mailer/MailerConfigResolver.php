<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Mailer;

use App\Shared\CharcoalApp;
use App\Shared\Config\Snapshot\MailerConfig;

final readonly class MailerConfigResolver
{
    public static function getMailerConfig(CharcoalApp $app): MailerConfig
    {
        return $app->config->mailer ??
            throw new \RuntimeException("Mailer is not configured");
    }
}