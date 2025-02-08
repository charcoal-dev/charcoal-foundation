<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\CoreData\BruteForceControl\BfcHandler;
use App\Shared\Foundation\CoreData\BruteForceControl\BfcTable;
use App\Shared\Foundation\CoreData\Countries\CountriesOrm;
use App\Shared\Foundation\CoreData\Countries\CountriesTable;
use App\Shared\Foundation\CoreData\DbBackups\DbBackupsHandler;
use App\Shared\Foundation\CoreData\DbBackups\DbBackupsTable;
use App\Shared\Foundation\CoreData\ObjectStore\ObjectStoreController;
use App\Shared\Foundation\CoreData\ObjectStore\ObjectStoreTable;
use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertsController;
use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertsTable;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\Cipher\Cipher;

/**
 * Class CoreDataModule
 * @package App\Shared\Foundation\CoreData
 */
class CoreDataModule extends AppOrmModule
{
    public ObjectStoreController $objectStore;
    public CountriesOrm $countries;
    public BfcHandler $bfc;
    public SystemAlertsController $alerts;
    public DbBackupsHandler $dbBackups;

    /**
     * @param AppBuildPartial $app
     * @param CoreData[] $components
     */
    public function __construct(AppBuildPartial $app, array $components)
    {
        parent::__construct($app, CacheStore::PRIMARY, $components);
    }


    public function getCipher(AbstractModuleComponent $resolveFor): ?Cipher
    {
        return null;
    }

    /**
     * @param CoreData|ModuleComponentEnum $component
     * @param AppBuildPartial $app
     * @return void
     */
    protected function includeComponent(CoreData|ModuleComponentEnum $component, AppBuildPartial $app): void
    {
        match ($component) {
            CoreData::OBJECT_STORE => $this->objectStore = new ObjectStoreController($this),
            CoreData::COUNTRIES => $this->countries = new CountriesOrm($this),
            CoreData::BFC => $this->bfc = new BfcHandler($this),
            CoreData::SYSTEM_ALERTS => $this->alerts = new SystemAlertsController($this),
            CoreData::DB_BACKUPS => $this->dbBackups = new DbBackupsHandler($this),
        };
    }

    /**
     * @param CoreData|ModuleComponentEnum $component
     * @param DatabaseTableRegistry $tables
     * @return void
     */
    protected function createDbTables(CoreData|ModuleComponentEnum $component, DatabaseTableRegistry $tables): void
    {
        match ($component) {
            CoreData::OBJECT_STORE => $tables->register(new ObjectStoreTable($this)),
            CoreData::COUNTRIES => $tables->register(new CountriesTable($this)),
            CoreData::BFC => $tables->register(new BfcTable($this)),
            CoreData::SYSTEM_ALERTS => $tables->register(new SystemAlertsTable($this)),
            CoreData::DB_BACKUPS => $tables->register(new DbBackupsTable($this)),
        };
    }
}