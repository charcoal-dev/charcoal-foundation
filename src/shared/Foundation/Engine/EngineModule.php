<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\CipherKey;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\Engine\ExecutionLog\ExecutionLogOrm;
use App\Shared\Foundation\Engine\ExecutionLog\ExecutionLogTable;
use App\Shared\Foundation\Engine\ExecutionLog\LogStatsOrm;
use App\Shared\Foundation\Engine\ExecutionLog\LogStatsTable;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\Cipher\Cipher;

/**
 * Class EngineModule
 * @package App\Shared\Foundation\Engine
 */
class EngineModule extends AppOrmModule
{
    public ExecutionLogOrm $executionLog;
    public LogStatsOrm $logStats;

    /**
     * @param AppBuildPartial $app
     * @param array $components
     */
    public function __construct(AppBuildPartial $app, array $components)
    {
        parent::__construct($app, CacheStore::PRIMARY, $components);
    }

    /**
     * @param AbstractModuleComponent $resolveFor
     * @return Cipher
     */
    public function getCipher(AbstractModuleComponent $resolveFor): Cipher
    {
        return $this->app->cipher->get(CipherKey::PRIMARY);
    }

    /**
     * @param AppBuildPartial $app
     * @return void
     */
    protected function declareChildren(AppBuildPartial $app): void
    {
        $this->executionLog = new ExecutionLogOrm($this);
        $this->logStats = new LogStatsOrm($this);
    }

    /**
     * @param DatabaseTableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(DatabaseTableRegistry $tables): void
    {
        $tables->register(new ExecutionLogTable($this));
        $tables->register(new LogStatsTable($this));
    }

    /**
     * @param ModuleComponentEnum $component
     * @param AppBuildPartial $app
     * @return bool
     */
    protected function includeComponent(ModuleComponentEnum $component, AppBuildPartial $app): bool
    {
        return false;
    }

    /**
     * @param ModuleComponentEnum $component
     * @param DatabaseTableRegistry $tables
     * @return bool
     */
    protected function createDbTables(ModuleComponentEnum $component, DatabaseTableRegistry $tables): bool
    {
        return false;
    }
}