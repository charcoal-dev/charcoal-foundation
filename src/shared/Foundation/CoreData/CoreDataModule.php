<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData;

use App\Shared\AppDbTables;
use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\CoreData\BruteForceControl\BfcHandler;
use App\Shared\Foundation\CoreData\BruteForceControl\BfcTable;
use App\Shared\Foundation\CoreData\Countries\CountriesOrm;
use App\Shared\Foundation\CoreData\Countries\CountriesTable;
use App\Shared\Foundation\CoreData\ObjectStore\ObjectStoreController;
use App\Shared\Foundation\CoreData\ObjectStore\ObjectStoreTable;
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

    protected function includeComponent(CoreData|ModuleComponentEnum $component, AppBuildPartial $app): void
    {
        match ($component) {
            CoreData::OBJECT_STORE => $this->objectStore = new ObjectStoreController($this, AppDbTables::OBJECT_STORE),
            CoreData::COUNTRIES => $this->countries = new CountriesOrm($this, AppDbTables::COUNTRIES),
            CoreData::BFC => $this->bfc = new BfcHandler($this, AppDbTables::BFC),
            default => throw new \LogicException("Cannot resolve component " . $component->name),
        };
    }

    protected function createDbTables(CoreData|ModuleComponentEnum $component, DatabaseTableRegistry $tables): void
    {
        match ($component) {
            CoreData::OBJECT_STORE => $tables->register(new ObjectStoreTable($this, AppDbTables::OBJECT_STORE)),
            CoreData::COUNTRIES => $tables->register(new CountriesTable($this, AppDbTables::COUNTRIES)),
            CoreData::BFC => $tables->register(new BfcTable($this, AppDbTables::BFC)),
        };
    }
}