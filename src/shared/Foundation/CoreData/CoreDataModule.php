<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData;

use App\Shared\Context\CacheStore;
use App\Shared\Context\CipherKey;
use App\Shared\Core\Orm\ComponentsAwareModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\CoreData\BruteForceControl\BruteForceLogger;
use App\Shared\Foundation\CoreData\BruteForceControl\BruteForceTable;
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
class CoreDataModule extends ComponentsAwareModule
{
    public ObjectStoreController $objectStore;
    public CountriesOrm $countries;
    public BruteForceLogger $bruteForce;
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

    /**
     * @param AbstractModuleComponent $resolveFor
     * @return Cipher
     */
    public function getCipher(AbstractModuleComponent $resolveFor): Cipher
    {
        return $this->app->cipher->get(CipherKey::PRIMARY);
    }

    /**
     * @param CoreData|ModuleComponentEnum $component
     * @param AppBuildPartial $app
     * @return bool
     */
    protected function includeComponent(CoreData|ModuleComponentEnum $component, AppBuildPartial $app): bool
    {
        switch ($component) {
            case CoreData::OBJECT_STORE:
                $this->objectStore = new ObjectStoreController($this);
                return true;
            case CoreData::COUNTRIES:
                $this->countries = new CountriesOrm($this);
                return true;
            case CoreData::BFC:
                $this->bruteForce = new BruteForceLogger($this);
                return true;
            case CoreData::SYSTEM_ALERTS:
                $this->alerts = new SystemAlertsController($this);
                return true;
            case CoreData::DB_BACKUPS:
                $this->dbBackups = new DbBackupsHandler($this);
                return true;
            default:
                return false;
        }
    }

    /**
     * @param CoreData|ModuleComponentEnum $component
     * @param DatabaseTableRegistry $tables
     * @return bool
     */
    protected function createDbTables(CoreData|ModuleComponentEnum $component, DatabaseTableRegistry $tables): bool
    {
        switch ($component) {
            case CoreData::OBJECT_STORE:
                $tables->register(new ObjectStoreTable($this));
                return true;
            case CoreData::COUNTRIES:
                $tables->register(new CountriesTable($this));
                return true;
            case CoreData::BFC:
                $tables->register(new BruteForceTable($this));
                return true;
            case CoreData::SYSTEM_ALERTS:
                $tables->register(new SystemAlertsTable($this));
                return true;
            case CoreData::DB_BACKUPS:
                $tables->register(new DbBackupsTable($this));
                return true;
            default:
                return false;
        }
    }
}