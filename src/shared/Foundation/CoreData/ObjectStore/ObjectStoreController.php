<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

use App\Shared\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Entity\EntitySource;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\Buffers\Buffer;
use Charcoal\Cache\Exception\CacheException;
use Charcoal\Cipher\Encrypted;
use Charcoal\Cipher\Exception\CipherException;
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class ObjectStoreController
 * @package App\Shared\Foundation\CoreData\ObjectStore
 * @property CoreDataModule $module
 */
class ObjectStoreController extends AbstractOrmRepository
{
    /**
     * @param CoreDataModule $module
     */
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::OBJECT_STORE);
    }

    /**
     * @param StoredObjectInterface $object
     * @param string|null $matchExp
     * @return void
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */
    public function store(StoredObjectInterface $object, ?string $matchExp = null): void
    {
        try {
            $storedObject = $object::ENCRYPTION->isEnabled() ?
                $this->getCipher()->encryptSerialize(clone $object)->raw() : serialize(clone $object);
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
     * @return void
     * @throws CacheException
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     */
    public function deleteFromCache(string $objectClasspath): void
    {
        $this->module->memoryCache->deleteFromCache($objectClasspath::getObjectStoreKey());
    }

    /**
     * @param class-string<StoredObjectInterface> $objectClasspath
     * @param bool $useCache
     * @return StoredObjectInterface
     * @throws CipherException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function get(string $objectClasspath, bool $useCache = true): StoredObjectInterface
    {
        $storageKey = $objectClasspath::getObjectStoreKey();
        $storedObject = $this->module->memoryCache->getFromMemory($storageKey);
        if ($storedObject instanceof StoredObjectInterface) {
            return $this->invokeStorageHooks($storedObject, EntitySource::RUNTIME, false);
        }

        if ($useCache) {
            try {
                $storedObject = $this->module->memoryCache->getFromCache($storageKey);
                if ($storedObject instanceof Encrypted) {
                    $storedObject = $this->decryptObject($objectClasspath, $storedObject);
                }

                if ($storedObject instanceof StoredObjectInterface && is_a($storedObject, $objectClasspath)) {
                    $this->module->memoryCache->storeInMemory($storageKey, $storedObject);
                    return $this->invokeStorageHooks($storedObject, EntitySource::CACHE);
                }
            } catch (CacheException $e) {
                trigger_error(static::class . ' caught CacheException', E_USER_NOTICE);
                $this->module->app->lifecycle->exception(
                    new \RuntimeException(static::class . ' caught CacheException', previous: $e),
                );
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
                $encryptedObject = Encrypted::Unserialize(new Buffer($storedObjectBytes),
                    $this->getCipher()->defaultMode->requiresTag());
            } catch (CipherException $e) {
                throw new \RuntimeException('CipherException caught ' . $e->error->name);
            }

            $storedObject = $this->decryptObject($objectClasspath, $encryptedObject);
        } else {
            $storedObject = unserialize($storedObjectBytes, ["allowed_classes" => $objectClasspath::childClasses()]);
        }

        if (!$storedObject instanceof StoredObjectInterface || !is_a($storedObject, $objectClasspath)) {
            throw new \RuntimeException(sprintf('%s encountered value of type "%s"', __METHOD__, gettype($storedObject)));
        }

        // Store in runtime memory
        $this->module->memoryCache->storeInMemory($storageKey, $storedObject);

        // Store in Cache?
        if (isset($useCache)) {
            $cacheObject = isset($encryptedObject) && $objectClasspath::ENCRYPTION->shouldCacheEncrypted() ?
                serialize(clone $encryptedObject) : clone $storedObject;

            try {
                $cacheTtl = max($objectClasspath::getCacheTtl(), $this->entityCacheTtl);
                $this->module->memoryCache->storeInCache($storageKey, $cacheObject, $cacheTtl > 0 ? $cacheTtl : null);
                $storedInCache = true;
            } catch (CacheException $e) {
                trigger_error(static::class . ' caught CacheException', E_USER_NOTICE);
                $this->module->app->lifecycle->exception(
                    new \RuntimeException(static::class . ' caught CacheException', previous: $e),
                );
            }
        }

        return $this->invokeStorageHooks($storedObject, EntitySource::DATABASE, $storedInCache ?? false);
    }

    /**
     * @param class-string<StoredObjectInterface> $objectClasspath
     * @param Encrypted $encryptedObject
     * @return mixed
     * @throws CipherException
     */
    private function decryptObject(string $objectClasspath, Encrypted $encryptedObject): mixed
    {
        return $this->getCipher()->decrypt($encryptedObject->bytes, $encryptedObject->iv, $encryptedObject->tag,
            allowedClasses: $objectClasspath::childClasses()
        );
    }
}