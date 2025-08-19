<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\DbBackups;

use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;

/**
 * This entity is used to store and manage information about a specific
 * database backup, including metadata such as backup ID, whether it was
 * created automatically, the associated database, timestamp, filename,
 * and size of the backup.
 */
class DbBackupEntity extends OrmEntityBase
{
    public int $id;
    /** @api */
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