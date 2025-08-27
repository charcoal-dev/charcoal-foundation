<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\Persisted\MailerConfig;
use App\Shared\Core\Config\Persisted\SmtpConfig;
use App\Shared\Enums\Mailer\MailProvider;
use App\Shared\Stubs\NullMailProvider;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Mailer\Agents\MailerAgentInterface;
use Charcoal\Mailer\Agents\SmtpClient;

/**
 * This class is responsible for resolving the appropriate mailer agent
 * based on the provided configuration.
 */
final readonly class MailerAgentResolver
{
    /**
     * @param CharcoalApp $app
     * @param MailerConfig $config
     * @return MailerAgentInterface
     * @throws WrappedException
     */
    public static function resolveProvider(CharcoalApp $app, MailerConfig $config): MailerAgentInterface
    {
        return match ($config->service) {
            MailProvider::SMTP => self::resolveSmtpAgent($app),
            default => new NullMailProvider(),
        };
    }

    /**
     * @param CharcoalApp $app
     * @return SmtpClient
     * @throws WrappedException
     */
    protected static function resolveSmtpAgent(CharcoalApp $app): SmtpClient
    {
        try {
            /** @var SmtpConfig $smtpConfig */
            $smtpConfig = $app->coreData->objectStore->get(SmtpConfig::class, true);
            return new SmtpClient(
                $smtpConfig->hostname,
                $smtpConfig->port,
                $smtpConfig->domain,
                $smtpConfig->encryption,
                $smtpConfig->username,
                $smtpConfig->password,
                $smtpConfig->timeout,
            );
        } catch (\Throwable $e) {
            throw new WrappedException($e, "Failed to resolve SMTP agent:" . $e::class);
        }
    }
}