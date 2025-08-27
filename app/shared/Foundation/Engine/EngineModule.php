<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine;

use App\Shared\CharcoalApp;
use App\Shared\Concerns\NormalizedStorageKeysTrait;
use App\Shared\Concerns\PendingModuleComponents;
use App\Shared\Enums\CacheStores;
use App\Shared\Foundation\Engine\Metrics\MetricsLogger;
use App\Shared\Foundation\Engine\Metrics\MetricsTable;
use App\Shared\Foundation\Engine\Logs\LogService;
use App\Shared\Foundation\Engine\Logs\LogsTable;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cache\CacheClient;

/**
 * Represents a module that extends the base ORM functionality.
 * Provides logging and metrics capabilities, while managing database table registration.
 * @property-read CharcoalApp $app
 */
final class EngineModule extends OrmModuleBase
{
    use PendingModuleComponents;
    use NormalizedStorageKeysTrait;

    public readonly LogService $executionLog;
    public readonly MetricsLogger $logStats;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->executionLog = new LogService($this);
        $this->logStats = new MetricsLogger($this);
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->executionLog = $data["executionLog"];
        $this->logStats = $data["logStats"];
        parent::__unserialize($data);
    }

    /**
     * Registers the necessary database tables for the application.
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new LogsTable($this));
        $tables->register(new MetricsTable($this));
    }

    /**
     * Retrieves the primary cache store.
     */
    public function getCacheStore(): ?CacheClient
    {
        return $this->app->cache->getStore(CacheStores::Primary);
    }
}