<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Builder;

use App\Shared\Core\Config\Builder\Traits\HttpConfigBuilderTrait;
use App\Shared\Core\Config\Http\ClientConfig;
use App\Shared\Core\Config\Http\RouterConfig;
use App\Shared\Core\Config\HttpConfig;
use App\Shared\Core\Config\Persisted\HttpInterfaceConfig;
use App\Shared\Enums\Http\HttpInterface;
use Charcoal\App\Kernel\Config\Builder\AbstractConfigObjectsCollector;
use Charcoal\App\Kernel\Internal\Config\ConfigBuilderInterface;

/**
 * A builder class responsible for constructing HTTP configuration objects.
 * Implements the ConfigBuilderInterface to ensure adherence to the contract
 * for building configuration snapshots.
 * @extends AbstractConfigObjectsCollector<HttpInterface, HttpInterfaceConfig, HttpConfig>
 * @implements ConfigBuilderInterface<HttpConfig>
 */
final class HttpConfigBuilder extends AbstractConfigObjectsCollector implements ConfigBuilderInterface
{
    use HttpConfigBuilderTrait;
    
    /**
     * @param HttpInterface $key
     * @param HttpInterfaceConfig $config
     * @return void
     */
    public function set(HttpInterface $key, HttpInterfaceConfig $config): void
    {
        $this->storeConfig($key, $config);
    }

    /**
     * @return HttpConfig
     */
    public function build(): HttpConfig
    {
        return new HttpConfig(
            new RouterConfig(),
            new ClientConfig(),
            $this->getCollection()
        );
    }
}