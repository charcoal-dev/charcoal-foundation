<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http;

use App\Shared\CharcoalApp;
use App\Shared\Concerns\NormalizedStorageKeysTrait;
use App\Shared\Concerns\PendingModuleComponents;
use App\Shared\Enums\CacheStores;
use App\Shared\Foundation\Http\CallLog\CallLogHandler;
use App\Shared\Foundation\Http\CallLog\CallLogTable;
use App\Shared\Foundation\Http\InterfaceLog\LogHandler;
use App\Shared\Foundation\Http\InterfaceLog\LogTable;
use App\Shared\Foundation\Http\ProxyServers\ProxiesHandler;
use App\Shared\Foundation\Http\ProxyServers\ProxiesTable;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cache\CacheClient;

/**
 * Class HttpModule
 * Represents an HTTP module handling various HTTP-related operations and components.
 * @property-read CharcoalApp $app
 */
final class HttpModule extends OrmModuleBase
{
    use PendingModuleComponents;
    use NormalizedStorageKeysTrait;

    public readonly CallLogHandler $callLog;
    public readonly LogHandler $interfaceLog;
    public readonly ProxiesHandler $proxies;

    protected ?HttpService $service = null;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->callLog = new CallLogHandler($this);
        $this->interfaceLog = new LogHandler($this);
        $this->proxies = new ProxiesHandler($this);
    }

    /**
     * @param TableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new CallLogTable($this));
        $tables->register(new LogTable($this));
        $tables->register(new ProxiesTable($this));
    }

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["callLog"] = $this->callLog;
        $data["interfaceLog"] = $this->interfaceLog;
        $data["proxies"] = $this->proxies;
        $data["service"] = null;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->callLog = $data["callLog"];
        $this->interfaceLog = $data["interfaceLog"];
        $this->proxies = $data["proxies"];
        $this->service = null;
        parent::__unserialize($data);
    }

    /**
     * @return HttpService
     */
    public function client(): HttpService
    {
        if (!$this->service) {
            $this->service = new HttpService($this->app);
        }

        return $this->service;
    }

    /**
     * @return CacheClient|null
     */
    public function getCacheStore(): ?CacheClient
    {
        return $this->app->cache->getStore(CacheStores::Primary);
    }
}