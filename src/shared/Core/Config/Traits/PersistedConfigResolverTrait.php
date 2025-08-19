<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Traits;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\Persisted\AbstractResolvedConfig;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Base\Support\Helpers\ObjectHelper;

/**
 * Provides functionality for resolving persisted configuration objects.
 */
trait PersistedConfigResolverTrait
{
    /**
     * Resolves and returns a static configuration object.
     */
    abstract protected function resolveStaticConfig(CharcoalApp $app): ?AbstractResolvedConfig;

    /**
     * Resolves and returns a configuration object based on the specified parameters.
     * @throws WrappedException
     */
    final protected function resolveConfigObject(
        CharcoalApp $app,
        string      $configClassname,
        bool        $useStatic,
        bool        $useObjectStore,
    ): AbstractResolvedConfig
    {
        $configObject = null;
        if ($useStatic) {
            $configObject = $this->resolveStaticConfig($app);
        }

        if ($configObject && is_a($configObject, $configClassname)) {
            return $configObject;
        }

        if ($useObjectStore) {
            if (isset($app->coreData->objectStore)) {
                try {
                    $configObject = $app->coreData->objectStore->get($configClassname);
                } catch (\Exception $e) {
                    throw new WrappedException($e, "Failed to retrieve " .
                        ObjectHelper::baseClassName($configClassname) . " from ObjectStore");
                }
            }
        }

        if (!isset($configObject)) {
            throw new \RuntimeException("Failed to resolve " . ObjectHelper::baseClassName($configClassname));
        }

        return $configObject;
    }
}