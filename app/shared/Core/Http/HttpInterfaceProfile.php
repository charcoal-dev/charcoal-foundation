<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\Persisted\AbstractResolvedConfig;
use App\Shared\Core\Config\Persisted\HttpInterfaceConfig;
use App\Shared\Core\Config\Traits\PersistedConfigResolverTrait;
use App\Shared\Enums\Http\HttpInterface;

/**
 * Class HttpInterfaceProfile
 * @package App\Shared\Core\Http
 */
class HttpInterfaceProfile
{
    public HttpInterfaceConfig $config;

    use PersistedConfigResolverTrait;

    /**
     * @param CharcoalApp $app
     * @param HttpInterface $enum
     * @param bool $useStaticConfig
     * @param bool $useObjectStoreConfig
     * @param string $configClass
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
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
     * @return AbstractResolvedConfig|null
     */
    protected function resolveStaticConfig(CharcoalApp $app): ?AbstractResolvedConfig
    {
        if (isset($app->config->http->interfaces[$this->enum->value])) {
            return $app->config->http->interfaces[$this->enum->value];
        }

        return null;
    }
}