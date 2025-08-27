<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Polyfill;

use App\Shared\Foundation\Mailer\Exception\EmailDeliveryException;
use Charcoal\Mailer\Agents\MailerAgentInterface;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Class NullMailProvider
 * @package App\Shared\Foundation\Mailer\Polyfill
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