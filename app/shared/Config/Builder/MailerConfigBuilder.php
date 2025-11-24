<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Builder;

use App\Shared\Config\Snapshot\MailerConfig;
use App\Shared\Enums\MailDispatchPolicy;
use App\Shared\Enums\MailProvider;
use Charcoal\App\Kernel\Internal\Config\ConfigBuilderInterface;
use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;
use Charcoal\Mailer\Contracts\MailProviderConfigInterface;

/**
 * A builder class responsible for constructing a configuration for the mailer system.
 * Implements the ConfigBuilderInterface to provide configuration snapshot output.
 */
final class MailerConfigBuilder implements ConfigBuilderInterface
{
    public MailProvider $agent = MailProvider::Disabled;
    public MailDispatchPolicy $policy = MailDispatchPolicy::Send_Only;
    public ?string $senderName = null;
    public ?string $senderEmail = null;
    public bool $queueProcessing = false;
    public int $queueRetryTimeout = 300;
    public int $queueExhaustAfter = 10;
    public int $queueTickInterval = 1;
    private array $transportConfigs = [];

    /**
     * @api
     */
    public function setTransportConfig(MailProvider $provider, MailProviderConfigInterface $config): self
    {
        $this->transportConfigs[$provider->value] = $config;
        return $this;
    }

    /**
     * @return MailerConfig
     */
    public function build(): MailerConfig
    {
        return new MailerConfig(
            $this->agent,
            $this->policy,
            $this->senderName,
            $this->senderEmail,
            $this->queueProcessing,
            $this->queueRetryTimeout,
            $this->queueExhaustAfter,
            $this->queueTickInterval,
            $this->transportConfigs
        );
    }
}