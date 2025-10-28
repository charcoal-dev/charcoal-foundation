<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Shared\Enums\CacheStores;
use App\Shared\Enums\SecretKeys;
use App\Shared\Enums\SemaphoreProviders;
use Charcoal\App\Kernel\Contracts\Enums\SemaphoreProviderEnumInterface;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Cache\CacheClient;
use Charcoal\Cipher\Cipher;
use Charcoal\Cipher\Support\CipherKeyRef;

/**
 * Trait ModuleCipherKeyTrait
 * @package App\Shared\Traits
 */
trait OrmModuleTrait
{
    private CipherKeyRef $cipherKeyRef;

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $this->ensureCipherKeyRef();
        $data = parent::collectSerializableData();
        $data["cipherKeyRef"] = $this->cipherKeyRef ?? null;
        return $data;
    }

    /**
     * @param OrmRepositoryBase $resolveFor
     * @return CipherKeyRef|null
     */
    public function getCipherFor(OrmRepositoryBase $resolveFor): ?CipherKeyRef
    {
        $this->ensureCipherKeyRef();
        return $this->cipherKeyRef;
    }

    /**
     * @return void
     */
    private function ensureCipherKeyRef(): void
    {
        if (!isset($this->cipherKeyRef)) {
            $this->cipherKeyRef = new CipherKeyRef(
                Cipher::AES_256_GCM,
                SecretKeys::CoreDataModule->getKeyRef()
            );
        }
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
        return $this->app->cache->getStore(CacheStores::Primary);
    }

    /**
     * @return SemaphoreProviderEnumInterface
     */
    public function getSemaphore(): SemaphoreProviderEnumInterface
    {
        return SemaphoreProviders::Local;
    }
}