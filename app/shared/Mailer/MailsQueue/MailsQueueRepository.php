<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Mailer\MailsQueue;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Mailer\Enums\MailQueueStatus;
use Charcoal\App\Kernel\Contracts\Orm\Repository\ChecksumAwareRepositoryInterface;
use Charcoal\App\Kernel\Entity\Exceptions\ChecksumComputeException;
use Charcoal\App\Kernel\Entity\Exceptions\ChecksumMismatchException;
use Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\ChecksumAwareRepositoryTrait;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityUpdatableTrait;
use Charcoal\App\Kernel\Support\ErrorHelper;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Types\Bytes20;
use Charcoal\Mailer\Message\CompiledMimeMessage;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Class MailsQueueRepository
 */
final class MailsQueueRepository extends OrmRepositoryBase implements
    ChecksumAwareRepositoryInterface
{
    use ChecksumAwareRepositoryTrait;
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    public function __construct()
    {
        parent::__construct(DatabaseTables::MailerQueue,
            AppConstants::ORM_CACHE_ERROR_HANDLING);
    }

    /**
     * @throws ChecksumComputeException
     * @throws EntityRepositoryException
     */
    public function addToQueue(
        CompiledMimeMessage $message,
        string              $recipient,
        \DateTimeImmutable  $timestamp,
    ): MailQueueEntity
    {
        $entity = $this->newEntityObject(MailQueueStatus::Queued, $message, $recipient, $timestamp, true);
        $this->dbInsertAndSetId($entity, "id");
        $entity->checksum = $this->calculateChecksum($entity);
        $this->dbUpdateChecksumAwareEntity($entity, new StringVector("checksum"), $entity->id, "id");
        return $entity;
    }

    /**
     * @param CompiledMimeMessage $message
     * @param string $recipient
     * @param \DateTimeImmutable $timestamp
     * @return MailQueueEntity
     * @throws ChecksumComputeException
     * @throws EntityRepositoryException
     */
    public function logSent(
        CompiledMimeMessage $message,
        string              $recipient,
        \DateTimeImmutable  $timestamp
    ): MailQueueEntity
    {
        $entity = $this->newEntityObject(MailQueueStatus::Sent, $message, $recipient, $timestamp, false);
        $this->dbInsertAndSetId($entity, "id");
        $this->markSent($entity, $timestamp);
        return $entity;
    }

    /**
     * @throws ChecksumComputeException
     * @throws EntityRepositoryException
     */
    public function logError(
        CompiledMimeMessage $message,
        string              $recipient,
        \DateTimeImmutable  $timestamp,
        ?\Exception         $error,
        bool                $exhausted = false,
    ): MailQueueEntity
    {
        $entity = $this->newEntityObject(MailQueueStatus::Error, $message, $recipient, $timestamp, false);
        $this->dbInsertAndSetId($entity, "id");
        $this->markError($entity, $exhausted, $timestamp, $error);
        return $entity;
    }

    /**
     * @throws ChecksumComputeException
     * @throws EntityRepositoryException
     */
    public function markError(
        MailQueueEntity    $entity,
        bool               $exhausted,
        \DateTimeImmutable $timestamp,
        ?\Exception        $e
    ): void
    {
        $entity->status = $exhausted ? MailQueueStatus::Exhausted :
            ($e ? MailQueueStatus::Error : MailQueueStatus::Queued);
        $entity->error = $e ? substr(ErrorHelper::exception2String($e), 0, 255) : null;
        $entity->attempts++;
        $entity->lastAttempt = $timestamp->getTimestamp();
        $this->dbUpdateChecksumAwareEntity($entity,
            new StringVector("status", "error", "attempts", "lastAttempt", "checksum"),
            $entity->id,
            "id"
        );
    }

    /**
     * @throws ChecksumComputeException
     * @throws EntityRepositoryException
     */
    public function markSent(MailQueueEntity $entity, \DateTimeImmutable $timestamp): void
    {
        $entity->status = MailQueueStatus::Sent;
        $entity->body = null;
        $entity->error = null;
        $entity->lastAttempt = $timestamp->getTimestamp();
        $this->dbUpdateChecksumAwareEntity($entity,
            new StringVector("status", "body", "error", "lastAttempt", "checksum"),
            $entity->id,
            "id"
        );
    }

    /**
     * Create a new entity.
     */
    private function newEntityObject(
        MailQueueStatus     $status,
        CompiledMimeMessage $message,
        string              $recipient,
        \DateTimeImmutable  $timestamp,
        bool                $includeBody
    ): MailQueueEntity
    {
        $queuedEmail = new MailQueueEntity();
        $queuedEmail->checksum = Bytes20::setPadded("tba");
        $queuedEmail->status = $status;
        $queuedEmail->recipient = $recipient;
        $queuedEmail->subject = $message->subject;
        $queuedEmail->body = $includeBody ? new Buffer(serialize(clone $message)) : null;
        $queuedEmail->attempts = 0;
        $queuedEmail->createdAt = $timestamp->getTimestamp();
        $queuedEmail->lastAttempt = null;
        $queuedEmail->error = null;
        return $queuedEmail;
    }

    /**
     * @param MailQueueEntity|null $entity
     * @return Bytes20
     * @throws ChecksumComputeException
     */
    public function calculateChecksum(MailQueueEntity $entity = null): Bytes20
    {
        return $this->entityChecksumCalculate($entity);
    }

    /**
     * @param MailQueueEntity|null $entity
     * @return bool
     * @throws ChecksumComputeException
     */
    public function verifyChecksum(MailQueueEntity $entity = null): bool
    {
        return $this->entityChecksumVerify($entity);
    }

    /**
     * @param MailQueueEntity|null $entity
     * @return void
     * @throws ChecksumComputeException
     * @throws ChecksumMismatchException
     */
    public function validateChecksum(MailQueueEntity $entity = null): void
    {
        $this->entityChecksumValidate($entity);
    }
}