<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

/**
 * Class StoredObjectEncryption
 * @package App\Shared\Foundation\CoreData\ObjectStore
 */
enum StoredObjectEncryption
{
    case DISABLED;
    case CACHE_ENCRYPTED;
    case CACHE_DECRYPTED;

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this !== self::DISABLED;
    }

    /**
     * @return bool
     */
    public function shouldCacheEncrypted(): bool
    {
        return $this === self::CACHE_ENCRYPTED;
    }
}