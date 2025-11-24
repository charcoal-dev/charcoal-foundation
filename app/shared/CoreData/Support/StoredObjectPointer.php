<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Support;

use App\Shared\Contracts\PersistedConfigObjectInterface;

/**
 * This class provides a way to uniquely identify and access persisted configuration objects.
 * Represents a reference to a stored object, consisting of its fully qualified class name,
 * a unique reference identifier, and a version number.
 */
final readonly class StoredObjectPointer
{
    /**
     * @param class-string<PersistedConfigObjectInterface> $fqcn
     * @param string $ref
     * @param int $version
     */
    public function __construct(
        public string $fqcn,
        public string $ref,
        public int    $version
    )
    {
    }
}