<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Backlog;

use App\Shared\Enums\Mailer\QueuedEmailStatus;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Buffers\Buffer;

/**
 * Represents an email queued for processing.
 * Holds information about the email and its status
 * in the queue, such as recipient, sender, subject,
 * attempts, and error information.
 */
final class QueuedEmail extends OrmEntityBase
{
    public int $id;
    public QueuedEmailStatus $status;
    public string $recipient;
    public string $sender;
    public string $subject;
    public ?Buffer $message;
    public int $addedOn;
    public int $attempts;
    public ?int $lastAttempt;
    public ?string $error;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        throw new \LogicException(self::class . " does not need to be serialized");
    }
}