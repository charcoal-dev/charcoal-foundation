<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\CipherKey;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\Engine\ExecutionLog\ExecutionLogOrm;
use App\Shared\Foundation\Engine\ExecutionLog\LogStatsOrm;
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

    protected function includeComponent(ModuleComponentEnum $component, AppBuildPartial $app): bool
    {
        // TODO: Implement includeComponent() method.
    }

    protected function createDbTables(ModuleComponentEnum $component, DatabaseTableRegistry $tables): bool
    {
        // TODO: Implement createDbTables() method.
    }
}