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
 * Class EngineModule
 * @package App\Shared\Foundation\Engine
 */
class EngineModule extends OrmModuleBase
{
    use PendingModuleComponents;
    use NormalizedStorageKeysTrait;

    public LogService $executionLog;
    public MetricsLogger $logStats;

    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->executionLog = new LogService($this);
        $this->logStats = new MetricsLogger($this);
    }

    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new LogsTable($this));
        $tables->register(new MetricsTable($this));
    }

    public function getCacheStore(): ?CacheClient
    {
        return $this->app->cache->getStore(CacheStores::Primary);
    }
}