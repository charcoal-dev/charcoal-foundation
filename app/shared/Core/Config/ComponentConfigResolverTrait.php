<?php
declare(strict_types=1);

namespace App\Shared\Core\Config;

use App\Shared\CharcoalApp;
use Charcoal\OOP\OOP;

/**
 * Trait ComponentConfigResolverTrait
 * @package App\Shared\Core\Config
 */
trait ComponentConfigResolverTrait
{
    /**
     * @param CharcoalApp $app
     * @return AbstractComponentConfig|null
     */
    abstract protected function resolveStaticConfig(CharcoalApp $app): ?AbstractComponentConfig;

    /**
     * @param CharcoalApp $app
     * @param class-string $configClassname
     * @param bool $useStatic
     * @param bool $useObjectStore
     * @return AbstractComponentConfig
     */
    protected function resolveConfigObject(
        CharcoalApp $app,
        string      $configClassname,
        bool        $useStatic,
        bool        $useObjectStore,
    ): AbstractComponentConfig
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
                    throw new \RuntimeException(
                        "Failed to retrieve " . OOP::baseClassName($configClassname) . " from ObjectStore",
                        previous: $e
                    );
                }
            }
        }

        if (!isset($configObject)) {
            throw new \RuntimeException("Failed to resolve " . OOP::baseClassName($configClassname));
        }

        return $configObject;
    }
}