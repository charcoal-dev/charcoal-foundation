<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\CoreData\Contracts\StorableObjectInterface;
use App\Shared\CoreData\Support\StoredObjectPointer;

/**
 * Marker interface for configuration objects stored in core data module
 */
interface PersistedConfigInterface extends StorableObjectInterface
{
    public static function getConfigKey(): string;

    public static function getCurrentPointer(): StoredObjectPointer;
}