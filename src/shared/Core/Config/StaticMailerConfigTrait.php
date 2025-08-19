<?php
declare(strict_types=1);

namespace App\Shared\Core\Config;

use App\Shared\Foundation\Mailer\Config\MailDispatchMode;
use App\Shared\Foundation\Mailer\Config\MailerConfig;
use App\Shared\Foundation\Mailer\Config\MailProvider;
use App\Shared\Utility\NetworkValidator;
use App\Shared\Utility\StringHelper;

/**
 * Trait StaticMailerConfigTrait
 * @package App\Shared\Core\Config
 * @deprecated
 */
trait StaticMailerConfigTrait
{
    /**
     * @param array|null $configData
     * @return MailerConfig|null
     */
    protected function getMailerConfig(array|null $configData): ?MailerConfig
    {
        if (!is_array($configData)) {
            return null;
        }

        $mailerConfig = new MailerConfig();

        // Service
        $service = MailProvider::tryFrom(strtolower(strval($configData["service"])));
        if (!$service) {
            throw new \OutOfBoundsException('Invalid mailer "service" configuration');
        }

        $mailerConfig->service = $service;

        // Policy
        $policy = MailDispatchMode::tryFrom(strtolower(strval($configData["policy"])));
        if (!$policy) {
            throw new \OutOfBoundsException('Invalid mailer "policy" configuration');
        }

        $mailerConfig->policy = $policy;

        // Sender, Queue, SMTP & API...
        $this->mailerConfigSender($mailerConfig, $configData);
        $this->mailerConfigBacklog($mailerConfig, $configData);
        $this->mailerConfigSmtp($mailerConfig, $configData);
        $this->mailerConfigApi($mailerConfig, $configData);

        // Crosschecks
        if ($mailerConfig->service === MailProvider::SMTP) {
            if (!$mailerConfig->smtpHostname || !$mailerConfig->smtpUsername || !$mailerConfig->smtpPassword) {
                throw new \DomainException('SMTP hostname and credentials required for SMTP mailer');
            }
        }

        if ($mailerConfig->service !== MailProvider::SMTP && $mailerConfig->service !== MailProvider::DISABLED) {
            if (!$mailerConfig->apiKey || !$mailerConfig->apiDomain) {
                throw new \DomainException('API credentials required for API-based mailer');
            }
        }

        return $mailerConfig;
    }

    /**
     * @param MailerConfig $config
     * @param array $configData
     * @return void
     */
    private function mailerConfigApi(MailerConfig $config, array $configData): void
    {
        $apiKey = StringHelper::getTrimmedOrNull($configData["api"]["key"] ?? null);
        if ($apiKey) {
            if (strlen($apiKey) > 80 || !ASCII::isPrintableOnly($apiKey)) {
                throw new \InvalidArgumentException('Invalid mailer "api->key" configuration');
            }
        }

        $domain = StringHelper::getTrimmedOrNull($configData["api"]["domain"] ?? null);
        if ($domain) {
            if (strlen($domain) > 80 || !ASCII::isPrintableOnly($domain)) {
                throw new \InvalidArgumentException('Invalid mailer "api->domain" configuration');
            }
        }

        $serverRegion = StringHelper::getTrimmedOrNull($configData["api"]["serverRegion"] ?? null);
        if ($serverRegion) {
            if (strlen($serverRegion) > 80 || !ASCII::isPrintableOnly($serverRegion)) {
                throw new \InvalidArgumentException('Invalid mailer "api->serverRegion" configuration');
            }
        }

        $timeout = $configData["api"]["timeout"] ?? null;
        if (!is_int($timeout) || $timeout < 1 || $timeout > 15) {
            throw new \InvalidArgumentException('Invalid mailer "api->timeout" configuration');
        }

        $connectTimeout = $configData["api"]["connectTimeout"] ?? null;
        if (!is_int($connectTimeout) || $connectTimeout < 1 || $connectTimeout > 15) {
            throw new \InvalidArgumentException('Invalid mailer "api->connectTimeout" configuration');
        }

        $config->apiKey = $apiKey;
        $config->apiDomain = $domain;
        $config->apiServerRegion = $serverRegion;
        $config->apiTimeout = $timeout;
        $config->apiConnectTimeout = $connectTimeout;
    }

    /**
     * @param MailerConfig $config
     * @param array $configData
     * @return void
     */
    private function mailerConfigSmtp(MailerConfig $config, array $configData): void
    {
        $smtpHostname = StringHelper::getTrimmedOrNull($configData["smtp"]["hostname"] ?? null);
        if ($smtpHostname) {
            if (!NetworkValidator::isValidHostname($smtpHostname, true, true, false)) {
                throw new \InvalidArgumentException('Invalid mailer "smtp->hostname" configuration');
            }
        }

        $smtpUsername = StringHelper::getTrimmedOrNull($configData["smtp"]["username"] ?? null);
        if ($smtpUsername) {
            if (!ASCII::isPrintableOnly($smtpUsername) || strlen($smtpUsername) > 80) {
                throw new \InvalidArgumentException('Invalid mailer "smtp->username" configuration');
            }
        }

        $smtpPassword = StringHelper::getTrimmedOrNull($configData["smtp"]["password"] ?? null);
        if ($smtpPassword) {
            if (!ASCII::isPrintableOnly($smtpPassword) || strlen($smtpPassword) > 80) {
                throw new \InvalidArgumentException('Invalid mailer "smtp->password" configuration');
            }
        }

        $smtpDomain = StringHelper::getTrimmedOrNull($configData["smtp"]["domain"] ?? null);
        if ($smtpDomain) {
            if (!ASCII::isPrintableOnly($smtpDomain) || strlen($smtpDomain) > 80) {
                throw new \InvalidArgumentException('Invalid mailer "smtp->domain" configuration');
            }
        }

        $smtpEncryption = $configData["smtp"]["encryption"] ?? null;
        if (!is_bool($smtpEncryption)) {
            throw new \InvalidArgumentException('Invalid mailer "smtp->encryption" configuration');
        }

        $smtpPort = $configData["smtp"]["port"] ?? null;
        if (!is_int($smtpPort) || $smtpPort < 1 || $smtpPort > 65535) {
            throw new \InvalidArgumentException('Invalid mailer "smtp->port" configuration');
        }

        $smtpTimeout = $configData["smtp"]["timeout"] ?? null;
        if (!is_int($smtpTimeout) || $smtpTimeout < 1 || $smtpTimeout > 15) {
            throw new \InvalidArgumentException('Invalid mailer "smtp->timeout" configuration');
        }

        $config->smtpHostname = $smtpHostname;
        $config->smtpUsername = $smtpUsername;
        $config->smtpPassword = $smtpPassword;
        $config->smtpDomain = $smtpDomain;
        $config->smtpEncryption = $smtpEncryption;
        $config->smtpPort = $smtpPort;
        $config->smtpTimeout = $smtpTimeout;
    }

    /**
     * @param MailerConfig $config
     * @param array $configData
     * @return void
     */
    private function mailerConfigBacklog(MailerConfig $config, array $configData): void
    {
        $processing = $configData["queue"]["processing"] ?? null;
        if (!is_bool($processing)) {
            throw new \InvalidArgumentException('Invalid mailer "queue->processing" configuration');
        }

        $retryTimeout = $configData["queue"]["retryTimeout"] ?? null;
        if (!is_int($retryTimeout) || $retryTimeout < 1 || $retryTimeout > 3600) {
            throw new \InvalidArgumentException('Invalid mailer "queue->retryTimeout" configuration');
        }

        $exhaustAfter = $configData["queue"]["exhaustAfter"] ?? null;
        if (!is_int($exhaustAfter) || $exhaustAfter < 1 || $exhaustAfter > 100) {
            throw new \InvalidArgumentException('Invalid mailer "queue->exhaustAfter" configuration');
        }

        $tickInterval = $configData["queue"]["tickInterval"] ?? null;
        if (!is_int($tickInterval) || $tickInterval < 1 || $tickInterval > 60) {
            throw new \InvalidArgumentException('Invalid mailer "queue->tickInterval" configuration');
        }

        $config->queueProcessing = $processing;
        $config->queueRetryTimeout = $retryTimeout;
        $config->queueExhaustAfter = $exhaustAfter;
        $config->queueTickInterval = $tickInterval;
    }

    /**
     * @param MailerConfig $config
     * @param array $configData
     * @return void
     */
    private function mailerConfigSender(MailerConfig $config, array $configData): void
    {
        $name = StringHelper::getTrimmedOrNull($configData["sender"]["name"] ?? null);
        if (!is_string($name) || !ASCII::isPrintableOnly($name) || strlen($name) < 2 || strlen($name) > 40) {
            throw new \InvalidArgumentException('Invalid mailer "sender->name" configuration');
        }

        $email = StringHelper::getTrimmedOrNull($configData["sender"]["email"] ?? null);
        if (!is_string($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 40) {
            throw new \InvalidArgumentException('Invalid mailer "sender->email" configuration');
        }

        $config->senderName = $name;
        $config->senderEmail = $email;
    }
}