<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

/**
 * Interface StoredObjectInterface
 * @package App\Shared\Foundation\CoreData\ObjectStore
 */
interface StoredObjectInterface
{
    public const StoredObjectEncryption ENCRYPTION = StoredObjectEncryption::DISABLED;

    public static function childClasses(): array;

    public static function getObjectStoreKey(): string;

    public static function getCacheTtl(): ?int;
}