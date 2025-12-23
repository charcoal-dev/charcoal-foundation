<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Traits;

use App\Shared\Enums\MailProvider;
use App\Shared\Mailer\Enums\MailDispatchPolicy;

/**
 * Provides functionality for configuring a mailer service using an array-based configuration file.
 */
trait MailerConfigBuilderTrait
{
    /**
     * @param mixed $mailerConfig
     * @return void
     */
    final protected function mailFromFileConfig(mixed $mailerConfig): void
    {
        if (!is_array($mailerConfig) || !$mailerConfig) {
            return;
        }

        // Resolve Enum Cases
        $transportEnum = MailProvider::tryFrom(strval($mailerConfig["transport"] ?? ""));
        if (!$transportEnum) {
            throw new \OutOfBoundsException("Invalid mail transport agent");
        }

        $transportPolicy = MailDispatchPolicy::tryFrom(strval($mailerConfig["policy" ?? ""]));
        if (!$transportPolicy) {
            throw new \OutOfBoundsException("Invalid mail transport policy");
        }

        $this->mailer->agent = $transportEnum;
        $this->mailer->policy = $transportPolicy;

        // Sender Params
        $this->mailer->senderName = strval($mailerConfig["sender"]["name"] ?? "");
        $this->mailer->senderEmail = strval($mailerConfig["sender"]["email"] ?? "");

        // Queue Processor
        $this->mailer->queueProcessing = boolval($mailerConfig["queue"]["processing"] ?? false);
        $this->mailer->queueRetryTimeout = intval($mailerConfig["queue"]["retryTimeout"] ?? 0);
        $this->mailer->queueExhaustAfter = intval($mailerConfig["queue"]["exhaustAfter"] ?? 0);
        $this->mailer->queueTickInterval = intval($mailerConfig["queue"]["tickInterval"] ?? 0);
    }
}