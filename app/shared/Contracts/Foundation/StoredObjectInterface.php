<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Contracts\Foundation;

use App\Shared\Enums\Foundation\StoredObjectEncryption;

/**
 * Provides methods for handling dependencies, retrieving object store keys,
 * and cache time-to-live values, as well as encryption settings.
 */
interface StoredObjectInterface
{
    public const StoredObjectEncryption ENCRYPTION = StoredObjectEncryption::Disabled;

    public static function unserializeDependencies(): array;

    public static function getObjectStoreKey(): string;

    public static function getCacheTtl(): ?int;
}