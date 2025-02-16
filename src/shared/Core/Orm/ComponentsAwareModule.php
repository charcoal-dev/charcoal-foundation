<?php
declare(strict_types=1);

namespace App\Shared\Core\Orm;

use App\Shared\Core\Cache\CacheStore;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\OOP\OOP;

/**
 * Class ComponentsAwareModule
 * @package App\Shared\Core\Orm
 */
abstract class ComponentsAwareModule extends AppOrmModule
{
    private array $components;

    /**
     * @param AppBuildPartial $app
     * @param CacheStore $cacheStore
     * @param array $components
     */
    protected function __construct(AppBuildPartial $app, CacheStore $cacheStore, array $components)
    {
        $this->components = $this->filterUniqueComponents($components);
        parent::__construct($app, $cacheStore);
    }

    /**
     * @param array $components
     * @return array
     */
    private function filterUniqueComponents(array $components): array
    {
        return array_values(array_reduce($components, function ($carry, $item) {
            if (!in_array($item, $carry, true)) {
                $carry[] = $item;
            }
            return $carry;
        }, []));
    }

    /**
     * @param AppBuildPartial $app
     * @return void
     */
    protected function declareChildren(AppBuildPartial $app): void
    {
        /** @var ModuleComponentEnum $component */
        foreach ($this->components as $component) {
            if (!$this->includeComponent($component, $app)) {
                throw new \LogicException(
                    sprintf('Unknown component "%s" for module "%s"',
                        $component->name,
                        OOP::baseClassName(static::class)
                    )
                );
            }
        }
    }

    /**
     * @param DatabaseTableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(DatabaseTableRegistry $tables): void
    {
        /** @var ModuleComponentEnum $component */
        foreach ($this->components as $component) {
            if (!$this->createDbTables($component, $tables)) {
                throw new \LogicException(
                    sprintf('Unknown component "%s" DB tables for module "%s"',
                        $component->name,
                        OOP::baseClassName(static::class)
                    )
                );
            }
        }
    }

    /**
     * @param ModuleComponentEnum $component
     * @param AppBuildPartial $app
     * @return bool
     */
    abstract protected function includeComponent(
        ModuleComponentEnum $component,
        AppBuildPartial     $app
    ): bool;

    /**
     * @param ModuleComponentEnum $component
     * @param DatabaseTableRegistry $tables
     * @return bool
     */
    abstract protected function createDbTables(
        ModuleComponentEnum   $component,
        DatabaseTableRegistry $tables
    ): bool;
}