<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Persistence;

use App\Shared\CoreData\Support\StoredObjectPointer;
use Charcoal\Base\Objects\Traits\UnserializeRestoreTrait;

/**
 * A trait for persisted config objects.
 */
trait PersistedConfigObjectTrait
{
    use UnserializeRestoreTrait;

    public const int CURRENT_VERSION = 1;

    abstract public static function getConfigKey(): string;

    /**
     * @return StoredObjectPointer
     */
    final public static function getCurrentPointer(): StoredObjectPointer
    {
        return new StoredObjectPointer(
            static::class,
            static::getConfigKey(),
            static::CURRENT_VERSION,
            validate: false
        );
    }

    /**
     * @return string
     */
    final public function getPrimaryId(): string
    {
        return static::getConfigKey();
    }
}