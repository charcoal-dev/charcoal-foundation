<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Persisted;

use App\Shared\Contracts\Config\PersistedConfigProvidesSnapshot;
use App\Shared\Contracts\Foundation\StoredObjectInterface;
use App\Shared\Enums\Mailer\MailDispatchMode;
use App\Shared\Enums\Mailer\MailProvider;
use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;

/**
 * Represents the configuration builder for mailer settings, allowing the setup of mail service parameters
 * and queue handling options.The class is responsible for defining and managing the configuration details
 * required for mail dispatching and processing.
 */
final class MailerConfig extends AbstractResolvedConfig implements
    PersistedConfigProvidesSnapshot, StoredObjectInterface
{
    public MailProvider $service = MailProvider::DISABLED;
    public MailDispatchMode $mode = MailDispatchMode::SEND_ONLY;
    public string $senderName;
    public string $senderEmail;

    public bool $queueProcessing = false;
    public int $queueRetryTimeout = 300;
    public int $queueExhaustAfter = 10;
    public int $queueTickInterval = 1;

    /**
     * @return ConfigSnapshotInterface
     */
    public function snapshot(): ConfigSnapshotInterface
    {
        return new \App\Shared\Core\Config\Snapshot\MailerConfig(
            $this->service,
            $this->mode,
            $this->senderName,
            $this->senderEmail,
            $this->queueProcessing,
            $this->queueRetryTimeout,
            $this->queueExhaustAfter,
            $this->queueTickInterval,
        );
    }
}