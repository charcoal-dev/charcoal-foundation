<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Auth;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\CipherKey;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\Auth\Sessions\SessionsHandler;
use App\Shared\Foundation\Auth\Sessions\SessionsTable;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\Cipher\Cipher;

/**
 * Class AuthModule
 * @package App\Shared\Foundation\Auth
 */
class AuthModule extends AppOrmModule
{
    public readonly SessionsHandler $sessions;

    /**
     * @param AppBuildPartial $app
     */
    public function __construct(AppBuildPartial $app)
    {
        parent::__construct($app, CacheStore::PRIMARY, []);
    }

    /**
     * @param AppBuildPartial $app
     * @return void
     */
    protected function declareChildren(AppBuildPartial $app): void
    {
        $this->sessions = new SessionsHandler($this);
    }

    /**
     * @param DatabaseTableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(DatabaseTableRegistry $tables): void
    {
        $tables->register(new SessionsTable($this));
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