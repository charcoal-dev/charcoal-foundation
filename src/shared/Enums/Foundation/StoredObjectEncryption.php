<?php
declare(strict_types=1);

namespace App\Shared\Enums\Foundation;

/**
 * Class StoredObjectEncryption
 * @package App\Shared\Enums\Foundation
 */
enum StoredObjectEncryption
{
    case Disabled;
    case CacheEncrypted;
    case CacheDecrypted;

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this !== self::Disabled;
    }

    /**
     * @return bool
     */
    public function shouldCacheEncrypted(): bool
    {
        return $this === self::CacheEncrypted;
    }
}