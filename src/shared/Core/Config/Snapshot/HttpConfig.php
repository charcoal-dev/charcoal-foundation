<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Snapshot;

use App\Shared\Core\Config\Http\ClientConfig;
use App\Shared\Core\Config\Http\ServerConfig;
use App\Shared\Enums\Http\HttpInterface;
use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;

/**
 * Represents the configuration for HTTP, encapsulating router and client settings
 * along with the configurations for specific HTTP interfaces.
 */
final readonly class HttpConfig implements ConfigSnapshotInterface
{
    /** @var array<string,HttpInterfaceConfig> */
    public array $interfaces;

    /**
     * @param ServerConfig $router
     * @param ClientConfig $client
     * @param array<string, \App\Shared\Core\Config\Persisted\HttpInterfaceConfig> $interfaces
     */
    public function __construct(
        public ServerConfig $router,
        public ClientConfig $client,
        array               $interfaces
    )
    {
        $final = [];
        if ($interfaces) {
            foreach ($interfaces as $interface => $config) {
                $interface = HttpInterface::tryFrom($interface);
                if (!$interface) {
                    throw new \OutOfBoundsException("No such HTTP interface declared");
                }

                $final[$interface->value] = $config->snapshot();
            }
        }

        $this->interfaces = $final;
    }
}