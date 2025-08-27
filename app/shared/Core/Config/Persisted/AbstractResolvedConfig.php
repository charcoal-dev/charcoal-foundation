<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Persisted;

use App\Shared\Contracts\Foundation\StoredObjectInterface;
use Charcoal\App\Kernel\Contracts\Orm\Entity\StorageHooksInterface;
use Charcoal\App\Kernel\Entity\AbstractEntity;
use Charcoal\Base\Enums\FetchOrigin;
use Charcoal\Base\Support\Helpers\ObjectHelper;

/**
 * Class AbstractResolvedConfig
 * @package App\Shared\Core\Config
 */
class AbstractResolvedConfig extends AbstractEntity
    implements StoredObjectInterface, StorageHooksInterface
{
    public const bool STORAGE_HOOKS = true;
    public const ?string CONFIG_ID = null;
    public const int CACHE_TTL = 86400;

    protected ?int $configCachedOn = null;

    /**
     * @return $this
     */
    public function getCacheableClone(): static
    {
        return clone $this;
    }

    /**
     * @return bool
     */
    public function isFromCache(): bool
    {
        return isset($this->configCachedOn) && is_int($this->configCachedOn) && $this->configCachedOn > 0;
    }

    /**
     * @return int|null
     */
    public function getCachedOn(): ?int
    {
        return $this->configCachedOn;
    }

    /**
     * @return array
     */
    final public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["configCachedOn"] = time();
        return $data;
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data = [];
        $reflection = new \ReflectionClass($this);
        $scope = \ReflectionProperty::IS_PUBLIC | \ReflectionProperty::IS_PROTECTED;
        foreach ($reflection->getProperties($scope) as $property) {
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
    public static function unserializeDependencies(): array
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

    /**
     * @param FetchOrigin $origin
     * @return string|null
     */
    public function onRetrieve(FetchOrigin $origin): ?string
    {
        if (!static::STORAGE_HOOKS) {
            return null;
        }

        if (in_array($origin, [FetchOrigin::Database, FetchOrigin::Cache])) {
            return sprintf('%s retrieved from %s', ObjectHelper::baseClassName(static::class), $origin->name);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function onCacheStore(): ?string
    {
        if (!static::STORAGE_HOOKS) {
            return null;
        }

        return ObjectHelper::baseClassName(static::class) . " stored in CACHE";
    }
}