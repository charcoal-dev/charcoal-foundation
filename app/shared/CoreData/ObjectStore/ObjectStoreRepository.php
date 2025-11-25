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
use App\Shared\CoreData\Support\StoredObjectPointer;
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
        $this->table->querySave($storeObject, new StringVector("payload", "kid", "updatedOn"));
    }

    /**
     * Returns the StoredObjectEntity object (metadata row and unserialized buffer of the stored object)
     * @throws EntityRepositoryException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     */
    public function getStoredEntity(StoredObjectPointer $objectPointer, bool $useCache): StoredObjectEntity
    {
        $cacheEntityId = $objectPointer->storageId . "_entity";

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
        $storedEntity = $this->getFromDb("ref=? AND version=?",
            [$objectPointer->ref, $objectPointer->version],
            invokeStorageHooks: false);
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
        StoredObjectPointer $objectPointer,
        bool                $useCache
    ): ?StorableObjectInterface
    {
        // From Runtime Memory
        $storedObject = $this->module->runtimeMemory->get($objectPointer->storageId);
        if ($storedObject instanceof StorableObjectInterface) {
            return $storedObject;
        }

        // From Caching Engine
        if ($useCache) {
            try {
                $storedObject = $this->module->getFromCache($objectPointer->storageId);
                if ($storedObject instanceof StorableObjectInterface) {
                    $this->module->runtimeMemory->store($objectPointer->storageId, $storedObject);
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
        StoredObjectPointer $objectPointer,
        bool                $useCache,
        ?array              $unserializeAllowedFqcn = null,
        ?SecretKeyInterface $secret = null,
        Cipher              $cipher = Cipher::AES_256_GCM
    ): StorableObjectInterface
    {
        $storedObject = $this->getObjectRuntime($objectPointer, $useCache);
        if ($storedObject) {
            return $storedObject;
        }

        $storedEntity = $this->getStoredEntity($objectPointer, $useCache);
        $storedObject = $storedEntity->getObject($unserializeAllowedFqcn, $secret, $cipher);

        // Store in Runtime Memory
        $this->module->runtimeMemory->store($objectPointer->storageId, $storedObject);

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
                $this->module->storeInCache($objectPointer->storageId, $storedObject,
                    max($this->entityCacheTtl, $storedObject->overrideCacheTtl()));
                $storedInCache = true;
            } catch (CacheException $e) {
                $this->handleCacheException($e);
            }
        }

        return $this->invokeStorageHooks($storedObject, FetchOrigin::Database, $storedInCache ?? false);
    }
}