<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\AbstractComponentConfig;
use App\Shared\Core\Config\ComponentConfigResolverTrait;
use App\Shared\Foundation\Http\Config\HttpInterfaceConfig;
use App\Shared\Foundation\Http\HttpInterface;

/**
 * Class HttpInterfaceProfile
 * @package App\Shared\Core\Http
 */
class HttpInterfaceProfile
{
    public HttpInterfaceConfig $config;

    use ComponentConfigResolverTrait;

    /**
     * @param CharcoalApp $app
     * @param HttpInterface $enum
     * @param bool $useStaticConfig
     * @param bool $useObjectStoreConfig
     * @param class-string $configClass
     */
    public function __construct(
        CharcoalApp          $app,
        public HttpInterface $enum,
        bool                 $useStaticConfig,
        bool                 $useObjectStoreConfig,
        string               $configClass = HttpInterfaceConfig::class,
    )
    {
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->config = $this->resolveConfigObject($app, $configClass, $useStaticConfig, $useObjectStoreConfig);
    }

    /**
     * @param CharcoalApp $app
     * @return AbstractComponentConfig|null
     */
    protected function resolveStaticConfig(CharcoalApp $app): ?AbstractComponentConfig
    {
        if (isset($app->config->http->interfaces[$this->enum->value])) {
            return $app->config->http->interfaces[$this->enum->value];
        }

        return null;
    }
}