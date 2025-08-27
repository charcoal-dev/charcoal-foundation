<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\SystemAlerts;

use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;
use Charcoal\Buffers\Buffer;

/**
 * Class SystemAlertEntity
 * @package App\Shared\Foundation\CoreData\SystemAlerts
 */
class SystemAlertEntity extends AbstractOrmEntity
{
    public int $id;
    public SystemAlertLevel $level;
    public string $message;
    public int $timestamp;
    public ?string $traceInterface;
    public ?int $traceQid;
    public ?Buffer $context = null;

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
        throw new \LogicException(static::class . " cannot be serialized");
    }
}