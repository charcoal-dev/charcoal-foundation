<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Mailer\MailsQueue;

use App\Shared\Mailer\Enums\MailQueueStatus;
use Charcoal\App\Kernel\Contracts\Orm\Entity\ChecksumAwareEntityInterface;
use Charcoal\App\Kernel\Entity\Traits\ChecksumAwareEntityTrait;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Buffers\Types\Bytes20;
use Charcoal\Contracts\Buffers\ReadableBufferInterface;

/**
 * Represents an entity for managing a mail queue with checksum validation.
 */
final class MailQueueEntity extends OrmEntityBase implements
    ChecksumAwareEntityInterface
{
    use ChecksumAwareEntityTrait;

    public int $id;
    public Bytes20 $checksum;
    public MailQueueStatus $status;
    public string $recipient;
    public string $subject;
    public ?ReadableBufferInterface $body = null;
    public int $attempts = 0;
    public int $createdAt;
    public ?int $lastAttempt = null;
    public ?string $error = null;

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
        return [
            "id" => $this->id,
            "checksum" => $this->checksum,
            "status" => $this->status,
            "recipient" => $this->recipient,
            "subject" => $this->subject,
            "body" => $this->body,
            "attempts" => $this->attempts,
            "createdAt" => $this->createdAt,
            "lastAttempt" => $this->lastAttempt,
            "error" => $this->error,
            "entityChecksumValidated" => $this->entityChecksumValidated
        ];
    }

    /**
     * @return array
     */
    public function collectChecksumData(): array
    {
        $data = $this->collectSerializableData();
        $data["body"] = $this->body ? strtolower(md5($this->body->bytes())) : null;
        unset($data["attempts"], $data["lastAttempt"], $data["error"], $data["entityChecksumValidated"]);
        return $data;
    }

    /**
     * @return Bytes20
     */
    public function getChecksum(): Bytes20
    {
        return $this->checksum;
    }
}