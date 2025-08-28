<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\Persisted\AbstractPersistedConfig;
use App\Shared\Core\Config\Traits\PersistedConfigResolverTrait;
use App\Shared\Enums\Http\HttpInterface;

/**
 * Class HttpInterfaceProfile
 * @package App\Shared\Core\Http
 */
class HttpInterfaceProfile
{
    use PersistedConfigResolverTrait;

    /**
     * @param CharcoalApp $app
     * @param HttpInterface $enum
     * @param bool $useStaticConfig
     * @param bool $useObjectStoreConfig
     * @return void
     */
    public function __construct(
        CharcoalApp          $app,
        public HttpInterface $enum,
        bool                 $useStaticConfig,
        bool                 $useObjectStoreConfig,
    )
    {
    }

    /**
     * @param CharcoalApp $app
     * @return AbstractPersistedConfig|null
     */
    protected function resolveStaticConfig(CharcoalApp $app): ?AbstractPersistedConfig
    {
        if (isset($app->config->http->interfaces[$this->enum->value])) {
            return $app->config->http->interfaces[$this->enum->value];
        }

        return null;
    }
}