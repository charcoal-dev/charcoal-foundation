<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Stubs;

use App\Shared\Exceptions\Mailer\EmailDeliveryException;
use Charcoal\Mailer\Agents\MailerAgentInterface;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * A mail provider implementation that does not send emails.
 * This class is intended to be used as a placeholder for cases where
 * no mailer agent is configured or email sending is intentionally disabled.
 */
class NullMailProvider implements MailerAgentInterface
{
    /**
     * @param Message|CompiledMimeMessage $message
     * @param array $recipients
     * @return int
     * @throws EmailDeliveryException
     */
    public function send(Message|CompiledMimeMessage $message, array $recipients): int
    {
        throw new EmailDeliveryException("No mailer agent configured");
    }
}