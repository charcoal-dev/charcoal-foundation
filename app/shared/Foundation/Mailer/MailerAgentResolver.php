<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use App\Shared\Foundation\Mailer\Config\MailerConfig;
use App\Shared\Foundation\Mailer\Config\MailProvider;
use App\Shared\Foundation\Mailer\Polyfill\NullMailProvider;
use Charcoal\Mailer\Agents\MailerAgentInterface;
use Charcoal\Mailer\Agents\SmtpClient;

/**
 * Class MailerAgentResolver
 * @package App\Shared\Foundation\Mailer
 */
class MailerAgentResolver
{
    /**
     * @param MailerConfig $config
     * @return MailerAgentInterface
     */
    public static function resolveProvider(MailerConfig $config): MailerAgentInterface
    {
        return match ($config->service) {
            MailProvider::SMTP => self::resolveSmtpAgent($config),
            default => new NullMailProvider(),
        };
    }

    /**
     * @param MailerConfig $config
     * @return SmtpClient
     */
    protected static function resolveSmtpAgent(MailerConfig $config): SmtpClient
    {
        if ($config->service !== MailProvider::SMTP) {
            throw new \BadMethodCallException(__METHOD__);
        }

        return new SmtpClient(
            $config->smtpHostname,
            $config->smtpPort,
            $config->smtpDomain,
            $config->smtpEncryption,
            $config->smtpUsername,
            $config->smtpPassword,
            $config->smtpTimeout,
        );
    }
}