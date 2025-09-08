<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

use App\Shared\Contracts\Foundation\StoredObjectInterface;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Buffers\Buffer;
use Charcoal\Cache\Exceptions\CacheException;
use Charcoal\Cache\Exceptions\CacheStoreOpException;
use Charcoal\Cipher\EncryptedEntity;
use Charcoal\Cipher\Exceptions\CipherException;
use Charcoal\Contracts\Errors\ExceptionAction;
use Charcoal\Contracts\Storage\Enums\FetchOrigin;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Provides functionality for storing, retrieving, caching, and managing encrypted or serialized objects
 * in a database or in memory. This service is designed to interact with the object store table
 * while adhering to security standards for encryption and caching.
 * @property CoreDataModule $module
 */
final class ObjectStoreService extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(DatabaseTables::ObjectStore);
    }

    /**
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    public function store(StoredObjectInterface $object, ?string $matchExp = null): void
    {
        try {
            $storedObject = $object::ENCRYPTION->isEnabled() ?
                $this->module->getCipherFor($this)
                    ->encryptSerialize(clone $object)->bytes() : serialize(clone $object);
        } catch (CipherException $e) {
            throw new \RuntimeException('CipherException caught ' . $e->error->name);
        }

        $this->table->querySave([
            "key" => $object::getObjectStoreKey(),
            "data_blob" => $storedObject,
            "match_rule" => $matchExp,
            "timestamp" => time()
        ], new StringVector("data_blob", "match_rule", "timestamp"));
    }

    /**
     * @param class-string<StoredObjectInterface> $objectClasspath
     * @throws CacheStoreOpException
     * @api
     */
    public function cachePurge(string $objectClasspath): void
    {
        $this->deleteFromCache($objectClasspath::getObjectStoreKey());
        $this->module->runtimeMemory->delete($objectClasspath::getObjectStoreKey());
    }

    /**
     * @param class-string<StoredObjectInterface> $objectClasspath
     * @param bool $useCache
     * @return StoredObjectInterface
     * @throws CipherException
     * @throws EntityRepositoryException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     */
    public function get(string $objectClasspath, bool $useCache = true): StoredObjectInterface
    {
        $storageKey = $objectClasspath::getObjectStoreKey();
        $storedObject = $this->module->runtimeMemory->get($storageKey);
        if ($storedObject instanceof StoredObjectInterface) {
            return $this->invokeStorageHooks($storedObject, FetchOrigin::Runtime, false);
        }

        if ($useCache) {
            try {
                $storedObject = $this->module->getFromCache($storageKey);
                if ($storedObject instanceof EncryptedEntity) {
                    $storedObject = $this->decryptObject($objectClasspath, $storedObject);
                }

                if ($storedObject instanceof StoredObjectInterface && is_a($storedObject, $objectClasspath)) {
                    $this->module->runtimeMemory->store($storageKey, $storedObject);
                    return $this->invokeStorageHooks($storedObject, FetchOrigin::Cache);
                }
            } catch (CacheException $e) {
                if ($this->onCacheException === ExceptionAction::Throw) {
                    throw new EntityRepositoryException($this, $e);
                } elseif ($this->onCacheException === ExceptionAction::Log) {
                    Diagnostics::app()->warning(self::class . ' caught CacheException', exception: $e);
                }
            }
        }

        // Get from Database
        $storedObject = $this->getFromDb("`key`=?", [$storageKey], invokeStorageHooks: false);
        $storedObjectBytes = $storedObject["data_blob"] ?? null;
        if (!is_string($storedObjectBytes) || empty($storedObjectBytes)) {
            throw new \RuntimeException(sprintf('%s encountered empty data_blob', __METHOD__));
        }

        if ($objectClasspath::ENCRYPTION->isEnabled()) {
            try {
                $encryptedObject = EncryptedEntity::Unserialize(new Buffer($storedObjectBytes),
                    $this->module->getCipherFor($this)->mode->requiresTag());
            } catch (CipherException $e) {
                throw new \RuntimeException('CipherException caught ' . $e->error->name);
            }

            $storedObject = $this->decryptObject($objectClasspath, $encryptedObject);
        } else {
            $storedObject = unserialize($storedObjectBytes,
                ["allowed_classes" => $objectClasspath::unserializeDependencies()]);
        }

        if (!$storedObject instanceof StoredObjectInterface || !is_a($storedObject, $objectClasspath)) {
            throw new \RuntimeException(sprintf('%s encountered value of type "%s"', __METHOD__, gettype($storedObject)));
        }

        // Store in runtime memory
        $this->module->runtimeMemory->store($storageKey, $storedObject);

        // Store in Cache?
        if (isset($useCache)) {
            $cacheObject = isset($encryptedObject) && $objectClasspath::ENCRYPTION->shouldCacheEncrypted() ?
                serialize(clone $encryptedObject) : clone $storedObject;

            try {
                $cacheTtl = max($objectClasspath::getCacheTtl(), $this->entityCacheTtl);
                $this->module->storeInCache($storageKey, $cacheObject, $cacheTtl > 0 ? $cacheTtl : null);
                $storedInCache = true;
            } catch (CacheException $e) {
                if ($this->onCacheException === ExceptionAction::Throw) {
                    throw new EntityRepositoryException($this, $e);
                } elseif ($this->onCacheException === ExceptionAction::Log) {
                    Diagnostics::app()->warning(self::class . ' caught CacheException', exception: $e);
                }
            }
        }

        return $this->invokeStorageHooks($storedObject, FetchOrigin::Database, $storedInCache ?? false);
    }

    /**
     * @param class-string<StoredObjectInterface> $objectClasspath
     * @param EncryptedEntity $encryptedObject
     * @return mixed
     * @throws CipherException
     */
    private function decryptObject(string $objectClasspath, EncryptedEntity $encryptedObject): mixed
    {
        return $this->module->getCipherFor($this)->decrypt(
            $encryptedObject->bytes,
            $encryptedObject->iv,
            $encryptedObject->tag,
            allowedClasses: $objectClasspath::uns()
        );
    }
}