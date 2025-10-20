<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\ObjectStore;

use App\Shared\AppConstants;
use App\Shared\CoreData\Contracts\StorableObjectInterface;
use App\Shared\CoreData\CoreDataModule;
use App\Shared\CoreData\Internal\CoreDataConstants;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Contracts\Orm\Entity\CacheableEntityInterface;
use Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Cache\Exceptions\CacheException;
use Charcoal\Cipher\Cipher;
use Charcoal\Contracts\Security\Secrets\SecretKeyInterface;
use Charcoal\Contracts\Storage\Enums\FetchOrigin;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Repository for ObjectStore entities.
 * @property CoreDataModule
 */
final class ObjectStoreRepository extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(
            DatabaseTables::ObjectStore,
            AppConstants::ORM_CACHE_ERROR_HANDLING
        );
    }

    /**
     * @param StoredObjectEntity $storeObject
     * @return void
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    public function store(StoredObjectEntity $storeObject): void
    {
        $this->table->querySave($storeObject, new StringVector("blob", "kid", "updatedOn"));
    }

    /**
     * Returns the StoredObjectEntity object (metadata row and unserialized buffer of the stored object)
     * @throws EntityRepositoryException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     */
    public function getStoredEntity(string $ref, int $version, bool $useCache): StoredObjectEntity
    {
        $cacheId = $this->validateFetchArguments($ref, $version);
        $cacheEntityId = $cacheId . "_entity";

        // From Runtime Memory
        $storedEntity = $this->module->runtimeMemory->get($cacheEntityId);
        if ($storedEntity instanceof StoredObjectEntity) {
            return $storedEntity;
        }

        // From Caching Engine
        if ($useCache) {
            try {
                $storedEntity = $this->module->getFromCache($cacheEntityId);
                if ($storedEntity instanceof StoredObjectEntity) {
                    $this->module->runtimeMemory->store($cacheEntityId, $storedEntity);
                    return $this->invokeStorageHooks($storedEntity, FetchOrigin::Cache);
                }
            } catch (CacheException $e) {
                $this->handleCacheException($e);
            }
        }

        // From Database
        $storedEntity = $this->getFromDb("ref=? AND version=?", [$ref, $version], invokeStorageHooks: false);
        if (!$storedEntity instanceof StoredObjectEntity) {
            throw new \UnexpectedValueException("Expected instance of " . StoredObjectEntity::class . "from DB, "
                . "got \"" . get_debug_type($storedEntity) . "\"");
        }

        // Store in Runtime Memory
        $this->module->runtimeMemory->store($cacheEntityId, $storedEntity);

        // Store in Cache?
        if ($useCache) {
            if (isset($storedEntity->kid)) {
                throw new \LogicException("Cannot cache encrypted StoredObjectEntity");
            }

            try {
                $this->module->storeInCache($cacheEntityId, $storedEntity, $this->entityCacheTtl);
                $storedInCache = true;
            } catch (CacheException $e) {
                $this->handleCacheException($e);
            }
        }

        return $this->invokeStorageHooks($storedEntity, FetchOrigin::Database, $storedInCache ?? false);
    }

    /**
     * Returns the actual object if it is already set in the runtime memory (or optionally, the caching engine)
     * @throws EntityRepositoryException
     * @throws WrappedException
     */
    public function getObjectRuntime(
        string $ref,
        int    $version,
        bool   $useCache
    ): ?StorableObjectInterface
    {
        $cacheId = $this->validateFetchArguments($ref, $version);

        // From Runtime Memory
        $storedObject = $this->module->runtimeMemory->get($cacheId);
        if ($storedObject instanceof StorableObjectInterface) {
            return $storedObject;
        }

        // From Caching Engine
        if ($useCache) {
            try {
                $storedObject = $this->module->getFromCache($cacheId);
                if ($storedObject instanceof StorableObjectInterface) {
                    $this->module->runtimeMemory->store($cacheId, $storedObject);
                    return $this->invokeStorageHooks($storedObject, FetchOrigin::Cache);
                }
            } catch (CacheException $e) {
                $this->handleCacheException($e);
            }
        }

        return null;
    }

    /**
     * Returns the actual object from runtime memory, cache, or database.
     * @throws EntityRepositoryException
     * @throws WrappedException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     */
    public function getObject(
        string              $ref,
        int                 $version,
        bool                $useCache,
        ?array              $unserializeAllowedFqcn = null,
        ?SecretKeyInterface $secret = null,
        Cipher              $cipher = Cipher::AES_256_GCM
    ): StorableObjectInterface
    {
        $storedObject = $this->getObjectRuntime($ref, $version, $useCache);
        if ($storedObject) {
            return $storedObject;
        }

        $cacheId = $this->generateCacheId($ref, $version);
        $storedEntity = $this->getStoredEntity($ref, $version, $useCache);
        $storedObject = $storedEntity->getObject($unserializeAllowedFqcn, $secret, $cipher);

        // Store in Runtime Memory
        $this->module->runtimeMemory->store($cacheId, $storedObject);

        // Store in Cache?
        if ($useCache) {
            if (!$storedObject instanceof CacheableEntityInterface) {
                throw new \LogicException("Cannot cache " . $storedObject::class
                    . "; Must implement CacheableEntityInterface");
            }

            /** @var StorableObjectInterface|CacheableEntityInterface $storedObject */

            if (isset($storedEntity->kid)) {
                throw new \LogicException("Cannot cache encrypted " . $storedObject::class);
            }

            try {
                $this->module->storeInCache($cacheId, $storedObject,
                    max($this->entityCacheTtl, $storedObject->overrideCacheTtl()));
                $storedInCache = true;
            } catch (CacheException $e) {
                $this->handleCacheException($e);
            }
        }

        return $this->invokeStorageHooks($storedObject, FetchOrigin::Database, $storedInCache ?? false);
    }

    /**
     * Validated the argument reference key and version before fetch/lookup.
     */
    private function validateFetchArguments(string $ref, int $version): string
    {
        // Bare validations
        if (!$ref || !preg_match(CoreDataConstants::STORED_OBJECT_REF_REGEXP, $ref)) {
            throw new \InvalidArgumentException("Invalid stored object reference: " . $ref);
        }

        if (!$version || $version < 1 || $version > 65535) {
            throw new \OutOfRangeException("Invalid stored object version: " . $version);
        }

        return $this->generateCacheId($ref, $version);
    }

    /**
     * No validations; Expects arguments to be valid.
     */
    private function generateCacheId(string $ref, int $version): string
    {
        return "objectStore:" . StoredObjectEntity::uniqueRefId($ref, $version);
    }
}