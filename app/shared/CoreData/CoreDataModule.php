<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData;

use App\Shared\CharcoalApp;
use App\Shared\CoreData\ObjectStore\ObjectStoreRepository;
use App\Shared\CoreData\ObjectStore\ObjectStoreTable;
use App\Shared\Enums\SecretKeys;
use App\Shared\Enums\SemaphoreProviders;
use Charcoal\App\Kernel\Contracts\Enums\SemaphoreProviderEnumInterface;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Cache\CacheClient;
use Charcoal\Cipher\Cipher;
use Charcoal\Cipher\Support\CipherKeyRef;
use Charcoal\Tests\App\Fixtures\Enums\CacheStore;

/**
 * This class provides functionality for normalizing storage keys,
 * accessing cache storage, declaring database tables, resolving ciphers
 * for specific ORM repositories, and retrieving semaphore providers.
 */
final class CoreDataModule extends OrmModuleBase
{
    public readonly ObjectStoreRepository $objectStore;
    private CipherKeyRef $cipherKeyRef;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->objectStore = new ObjectStoreRepository();
    }

    /**
     * @param TableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new ObjectStoreTable($this));
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["cipherKeyRef"] = $this->cipherKeyRef;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->objectStore = $data["objectStore"];
        $this->cipherKeyRef = $data["cipherKeyRef"];
        parent::__unserialize($data);
    }

    /**
     * @param string $key
     * @return string
     */
    public function normalizeStorageKey(string $key): string
    {
        return strtolower(trim($key));
    }

    /**
     * @return CacheClient
     */
    public function getCacheStore(): CacheClient
    {
        return $this->app->cache->getStore(CacheStore::Primary);
    }

    /**
     * @param OrmRepositoryBase $resolveFor
     * @return CipherKeyRef|null
     */
    public function getCipherFor(OrmRepositoryBase $resolveFor): ?CipherKeyRef
    {
        if (!isset($this->cipherKeyRef)) {
            $this->cipherKeyRef = new CipherKeyRef(
                Cipher::AES_256_GCM,
                SecretKeys::CoreDataModule->getKeyRef()
            );
        }

        return $this->cipherKeyRef;
    }

    /**
     * @return SemaphoreProviderEnumInterface
     */
    public function getSemaphore(): SemaphoreProviderEnumInterface
    {
        return SemaphoreProviders::Local;
    }
}