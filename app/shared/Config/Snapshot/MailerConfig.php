<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Snapshot;

use App\Shared\Enums\MailDispatchPolicy;
use App\Shared\Enums\MailProvider;
use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;
use Charcoal\App\Kernel\Support\ContactHelper;
use Charcoal\Mailer\Contracts\MailProviderConfigInterface;

/**
 * This class defines properties required for configuring a mail dispatch system.
 * It includes information about the mail provider, dispatch policy, sender details,
 * and settings related to queue processing behavior.
 */
final readonly class MailerConfig implements ConfigSnapshotInterface
{
    /** @var array<string, MailProviderConfigInterface> */
    private array $transportConfigs;

    public function __construct(
        public MailProvider       $agent,
        public MailDispatchPolicy $policy,
        public string             $senderName,
        public string             $senderEmail,
        public bool               $queueProcessing,
        public int                $queueRetryTimeout = 300,
        public int                $queueExhaustAfter = 10,
        public int                $queueTickInterval = 1,
        array                     $providerConfigs,
    )
    {
        // Cross-check configuration objects
        if ($this->agent !== MailProvider::Disabled) {
            throw new \OutOfBoundsException($this->agent->name . " configuration object required");
        }

        // Validate Sender Name & Email
        if (!preg_match('/\A[A-Za-z0-9\-.!$%&+#@]+(\s[A-Za-z0-9\-.!$%&+#@]+)*\z/', $this->senderName)) {
            throw new \InvalidArgumentException("Invalid sender name");
        }

        if (!ContactHelper::isValidEmailAddress($this->senderEmail, true, 64)) {
            throw new \InvalidArgumentException("Invalid sender email address");
        }

        // Queue Processing Vars
        if ($this->queueRetryTimeout < 10 || $this->queueRetryTimeout > 86400) {
            throw new \OutOfRangeException("Invalid queue retry timeout value: " . $this->queueRetryTimeout);
        }

        if ($this->queueExhaustAfter < 1 || $this->queueExhaustAfter > 100) {
            throw new \OutOfRangeException("Invalid queue exhaust after value: " . $this->queueExhaustAfter);
        }

        if ($this->queueTickInterval < 1 || $this->queueTickInterval > 300) {
            throw new \OutOfRangeException("Invalid queue tick interval value: " . $this->queueTickInterval);
        }

        // Provider Configurations
        $finalConfigs = [];
        if ($providerConfigs) {
            foreach ($providerConfigs as $providerId => $providerConfig) {
                if (!$providerConfig instanceof MailProviderConfigInterface) {
                    throw new \InvalidArgumentException("Invalid mail provider configuration object: " .
                        get_debug_type($providerConfig));
                }

                $providerEnum = MailProvider::tryFrom(strval($providerId));
                if (!$providerEnum) {
                    throw new \InvalidArgumentException("Invalid MailProvider enum: " . $providerId);
                } elseif ($providerEnum === MailProvider::Disabled) {
                    throw new \InvalidArgumentException("Configuration cannot be stored for Disabled enum case");
                }

                $finalConfigs[$providerEnum->value] = $providerConfig;
            }
        }

        $this->transportConfigs = $finalConfigs;
    }

    /**
     * @param MailProvider $provider
     * @return MailProviderConfigInterface|null
     */
    public function getTransportConfig(MailProvider $provider): ?MailProviderConfigInterface
    {
        return $this->transportConfigs[$provider->value] ?? null;
    }
}