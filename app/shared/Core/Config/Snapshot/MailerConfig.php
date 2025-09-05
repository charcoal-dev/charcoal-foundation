<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Snapshot;

use App\Shared\Enums\Mailer\MailDispatchMode;
use App\Shared\Enums\Mailer\MailProvider;
use App\Shared\Utility\ContactHelper;
use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;
use Charcoal\Charsets\Support\AsciiHelper;

/**
 * This class contains configuration details for setting up email dispatching
 * through a specific mail provider with a defined dispatch policy. It includes
 * validations for sender details and queue processing parameters, ensuring the
 * configuration adheres to expected constraints before it is used.
 */
final readonly class MailerConfig implements ConfigSnapshotInterface
{
    public function __construct(
        public MailProvider     $service,
        public MailDispatchMode $mode,
        public string           $senderName,
        public string           $senderEmail,
        public bool             $queueProcessing,
        public int              $queueRetryTimeout,
        public int              $queueExhaustAfter,
        public int              $queueTickInterval,
    )
    {
        // Sender Validations
        if (!AsciiHelper::isPrintableOnly($this->senderName) ||
            strlen($this->senderName) < 2 ||
            strlen($this->senderName) > 40
        ) {
            throw new \InvalidArgumentException("Invalid sender name configured for mailer");
        }

        if (!ContactHelper::isValidEmailAddress($this->senderEmail)) {
            throw new \InvalidArgumentException("Invalid sender email configured for mailer");
        }

        // Queue Processing Validations
        if (max(1, min(3600, $this->queueRetryTimeout)) !== $this->queueRetryTimeout) {
            throw new \OutOfRangeException("Invalid mailer queueRetryTimeout configured");
        }

        if (max(1, min(100, $this->queueExhaustAfter)) !== $this->queueExhaustAfter) {
            throw new \OutOfRangeException("Invalid mailer queueExhaustAfter configured");
        }

        if (max(1, min(60, $this->queueTickInterval)) !== $this->queueTickInterval) {
            throw new \OutOfRangeException("Invalid mailer queueTickInterval configured");
        }
    }
}