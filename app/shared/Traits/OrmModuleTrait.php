<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Traits;

use App\Shared\Enums\CacheStores;
use App\Shared\Enums\SemaphoreScopes;
use Charcoal\App\Kernel\Contracts\Enums\SemaphoreScopeEnumInterface;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\RepositoryCipherRef;
use Charcoal\Cache\CacheClient;

/**
 * Trait ModuleCipherKeyTrait
 * @package App\Shared\Traits
 */
trait OrmModuleTrait
{
    /**
     * @param OrmRepositoryBase $repo
     * @return RepositoryCipherRef|null
     */
    public function getCipherFor(OrmRepositoryBase $repo): ?RepositoryCipherRef
    {
        if (!$this->security || !$this->security->cipherAlgo) {
            return null;
        }

        $moduleSecret = $this->getSecretKey();
        if (!$moduleSecret) {
            return null;
        }

        return new RepositoryCipherRef(
            $this->security->cipherAlgo,
            $moduleSecret
        );
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
     * @return SemaphoreScopeEnumInterface
     */
    public function getSemaphore(): SemaphoreScopeEnumInterface
    {
        return SemaphoreScopes::Orm;
    }
}