<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Backlog;

use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;
use Charcoal\Buffers\Buffer;

/**
 * Class QueuedEmail
 * @package App\Shared\Foundation\Mailer\Backlog
 */
class QueuedEmail extends AbstractOrmEntity
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
        throw new \LogicException(static::class . " does not need to be serialized");
    }
}