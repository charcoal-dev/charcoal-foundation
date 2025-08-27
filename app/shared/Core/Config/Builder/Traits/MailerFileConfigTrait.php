<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use App\Shared\Core\Config\Persisted\MailerConfig;
use App\Shared\Enums\Mailer\MailDispatchMode;
use App\Shared\Enums\Mailer\MailProvider;
use App\Shared\Utility\StringHelper;

/**
 * Provides functionality to construct a MailerConfig object from a configuration array.
 * Validates configuration data and ensures required fields meet the expected types.
 * @api
 */
trait MailerFileConfigTrait
{
    /**
     * @api
     */
    protected function getMailerConfig(mixed $configData): ?MailerConfig
    {
        if (!is_array($configData) || !$configData) {
            return null;
        }

        // Service
        $service = MailProvider::tryFrom(strtolower(strval($configData["service"])));
        if (!$service) {
            throw new \OutOfBoundsException('Invalid mailer "service" configuration');
        }

        // Dispatch Mode
        $mode = MailDispatchMode::tryFrom(strtolower(strval($configData["mode"])));
        if (!$mode) {
            throw new \OutOfBoundsException('Invalid mailer "mode" configuration');
        }

        // Sender
        $name = StringHelper::getTrimmedOrNull($configData["sender"]["name"] ?? null);
        if (!is_string($name) || !$name) {
            throw new \InvalidArgumentException('Invalid mailer "sender->name" configuration');
        }

        $email = StringHelper::getTrimmedOrNull($configData["sender"]["email"] ?? null);
        if (!is_string($email) || !$email) {
            throw new \InvalidArgumentException('Invalid mailer "sender->email" configuration');
        }

        // Backlog/Queue
        $processing = $configData["queue"]["processing"] ?? null;
        if (!is_bool($processing)) {
            throw new \InvalidArgumentException('Invalid mailer "queue->processing" configuration');
        }

        $retryTimeout = $configData["queue"]["retryTimeout"] ?? null;
        if (!is_int($retryTimeout)) {
            throw new \InvalidArgumentException('Invalid mailer "queue->retryTimeout" configuration');
        }

        $exhaustAfter = $configData["queue"]["exhaustAfter"] ?? null;
        if (!is_int($exhaustAfter)) {
            throw new \InvalidArgumentException('Invalid mailer "queue->exhaustAfter" configuration');
        }

        $tickInterval = $configData["queue"]["tickInterval"] ?? null;
        if (!is_int($tickInterval)) {
            throw new \InvalidArgumentException('Invalid mailer "queue->tickInterval" configuration');
        }

        $mailerConfig = new MailerConfig();
        $mailerConfig->service = $service;
        $mailerConfig->mode = $mode;
        $mailerConfig->senderName = $name;
        $mailerConfig->senderEmail = $email;
        $mailerConfig->queueProcessing = $processing;
        $mailerConfig->queueRetryTimeout = $retryTimeout;
        $mailerConfig->queueExhaustAfter = $exhaustAfter;
        $mailerConfig->queueTickInterval = $tickInterval;
        return $mailerConfig;
    }
}