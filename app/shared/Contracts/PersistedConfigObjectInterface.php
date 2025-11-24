<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\CoreData\Contracts\StorableObjectInterface;

/**
 * Marker interface for configuration objects stored in core data module
 */
interface PersistedConfigObjectInterface extends StorableObjectInterface
{
}