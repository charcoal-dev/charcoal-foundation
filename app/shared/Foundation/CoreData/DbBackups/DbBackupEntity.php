<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\DbBackups;

use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;

/**
 * Class DbBackupEntity
 * @package App\Shared\Foundation\CoreData\DbBackups
 */
class DbBackupEntity extends AbstractOrmEntity
{
    public int $id;
    public bool $isAuto;
    public string $database;
    public int $timestamp;
    public string $filename;
    public int $size;

    public function getPrimaryId(): int
    {
        return $this->id;
    }

    protected function collectSerializableData(): array
    {
        throw new \LogicException(static::class . " does not need to be serialized");
    }
}