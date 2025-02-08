<?php
declare(strict_types=1);

namespace App\Shared\Core\Config;

use App\Shared\Foundation\CoreData\ObjectStore\StoredObjectInterface;
use Charcoal\App\Kernel\Entity\AbstractEntity;

/**
 * Class AbstractComponentConfig
 * @package App\Shared\Core\Config
 */
class AbstractComponentConfig extends AbstractEntity implements StoredObjectInterface
{
    public const ?string CONFIG_ID = null;
    public const int CACHE_TTL = 86400;

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED) as $property) {
            $prop = $property->name;
            if (isset($this->$prop)) {
                $data[$prop] = $this->$prop;
            }
        }

        return $data;
    }

    /**
     * @return \class-string[]
     */
    public static function childClasses(): array
    {
        return [static::class];
    }

    /**
     * @return string
     */
    final public function getPrimaryId(): string
    {
        return static::getObjectStoreKey();
    }

    /**
     * @return string
     */
    final public static function getObjectStoreKey(): string
    {
        if (static::CONFIG_ID === null || static::CONFIG_ID === "") {
            throw new \LogicException(sprintf('CONFIG_ID must be defined in class "%s"', static::class));
        }

        return static::CONFIG_ID;
    }

    /**
     * @return int
     */
    public static function getCacheTtl(): int
    {
        if (!is_int(static::CACHE_TTL) || static::CACHE_TTL < 1) {
            throw new \LogicException(sprintf('CACHE_TTL must be defined in class "%s"', static::class));
        }

        return static::CACHE_TTL;
    }
}