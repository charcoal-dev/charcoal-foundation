<?php
declare(strict_types=1);

namespace App\Shared\Exceptions\Mailer;

use Charcoal\Mailer\Agents\MailerAgentInterface;

/**
 * Class EmailDeliveryException
 * @package App\Shared\Exceptions\Mailer
 */
class EmailDeliveryException extends EmailServiceException
{
    /**
     * Use this method to define agent-specific error messages/codes for EmailDeliveryException
     * @param MailerAgentInterface $agent
     * @param \Throwable $previous
     * @return static
     */
    public static function fromAgentContext(MailerAgentInterface $agent, \Throwable $previous): static
    {
        if ($previous instanceof static) {
            return $previous;
        }

        return new static(
            sprintf('Email delivery with agent "%s" failed', get_class($agent)),
            previous: $previous);
    }
}