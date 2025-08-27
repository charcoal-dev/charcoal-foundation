<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Concerns;

/**
 * Provides functionality to normalize storage keys by applying consistent formatting.
 */
trait NormalizedStorageKeysTrait
{
    /**
     * Normalizes the given storage key by trimming whitespace and converting it to lowercase.
     */
    public function normalizeStorageKey(string $key): string
    {
        return trim(strtolower($key));
    }
}