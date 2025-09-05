<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Traits;

use App\Shared\CharcoalApp;
use App\Shared\Contracts\Foundation\StoredObjectInterface;
use App\Shared\Core\Config\Persisted\AbstractPersistedConfig;
use Charcoal\Base\Objects\ObjectHelper;

/**
 * Provides functionality for resolving persisted configuration objects.
 */
trait PersistedConfigResolverTrait
{
    /**
     * Resolves and returns a static configuration object.
     */
    abstract protected function resolveStaticConfig(CharcoalApp $app): ?AbstractPersistedConfig;

    /**
     * @param CharcoalApp $app
     * @param class-string<StoredObjectInterface> $configClassname
     * @param bool $useStatic
     * @param bool $useObjectStore
     * @return AbstractPersistedConfig
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     */
    final protected function resolveConfigObject(
        CharcoalApp $app,
        string      $configClassname,
        bool        $useStatic,
        bool        $useObjectStore,
    ): AbstractPersistedConfig
    {
        $configObject = null;
        if ($useStatic) {
            $configObject = $this->resolveStaticConfig($app);
        }

        if ($configObject && is_a($configObject, $configClassname)) {
            return $configObject;
        }

        if ($useObjectStore) {
            $configObject = $app->coreData->objectStore->get($configClassname);
        }

        if (!isset($configObject)) {
            throw new \RuntimeException("Failed to resolve " . ObjectHelper::baseClassName($configClassname));
        }

        return $configObject;
    }
}