<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums\Foundation;

/**
 * Represents the encryption states for a stored object.
 * Provides methods to determine if encryption is enabled
 * or if encrypted caching should be applied.
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