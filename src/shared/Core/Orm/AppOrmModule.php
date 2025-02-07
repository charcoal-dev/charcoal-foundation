<?php
declare(strict_types=1);

namespace App\Shared\Core\Orm;

use App\Shared\CharcoalApp;
use App\Shared\Core\Cache\CacheStore;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Orm\AbstractOrmModule;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;

/**
 * Class AppOrmModule
 * @package App\Shared\Core\Orm
 * @property CharcoalApp $app
 */
abstract class AppOrmModule extends AbstractOrmModule
{
    private array $components;

    /**
     * @param AppBuildPartial $app
     * @param CacheStore $cacheStore
     * @param ModuleComponentEnum[] $components
     */
    protected function __construct(AppBuildPartial $app, CacheStore $cacheStore, array $components)
    {
        $this->components = array_unique($components);
        parent::__construct($app, $cacheStore);
    }

    final protected function declareChildren(AppBuildPartial $app): void
    {
        /** @var ModuleComponentEnum $component */
        foreach ($this->components as $component) {
            $this->includeComponent($component, $app);
        }
    }

    final protected function declareDatabaseTables(DatabaseTableRegistry $tables): void
    {
        /** @var ModuleComponentEnum $component */
        foreach ($this->components as $component) {
            $this->createDbTables($component, $tables);
        }
    }

    abstract protected function includeComponent(
        ModuleComponentEnum $component,
        AppBuildPartial     $app
    ): void;

    abstract protected function createDbTables(
        ModuleComponentEnum   $component,
        DatabaseTableRegistry $tables
    ): void;
}