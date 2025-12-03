<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData;

use App\Shared\CharcoalApp;
use App\Shared\CoreData\Bfc\BfcRepository;
use App\Shared\CoreData\Bfc\BfcTable;
use App\Shared\CoreData\Countries\CountriesRepository;
use App\Shared\CoreData\Countries\CountriesTable;
use App\Shared\CoreData\ObjectStore\ObjectStoreRepository;
use App\Shared\CoreData\ObjectStore\ObjectStoreTable;
use App\Shared\Enums\SecretKeys;
use App\Shared\Traits\OrmModuleTrait;
use Charcoal\App\Kernel\Domain\ModuleSecurityBindings;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cipher\Cipher;

/**
 * This class provides functionality for normalizing storage keys,
 * accessing cache storage, declaring database tables, resolving ciphers
 * for specific ORM repositories, and retrieving semaphore providers.
 */
final class CoreDataModule extends OrmModuleBase
{
    use OrmModuleTrait;

    public readonly BfcRepository $bfc;
    public readonly ObjectStoreRepository $objectStore;
    public readonly CountriesRepository $countries;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->bfc = new BfcRepository();
        $this->objectStore = new ObjectStoreRepository();
        $this->countries = new CountriesRepository();
    }

    /**
     * @param TableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new BfcTable($this));
        $tables->register(new ObjectStoreTable($this));
        $tables->register(new CountriesTable($this));
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->bfc = $data["bfc"];
        $this->countries = $data["countries"];
        $this->objectStore = $data["objectStore"];
        parent::__unserialize($data);
    }

    /**
     * @return ModuleSecurityBindings
     */
    protected function declareSecurityBindings(): ModuleSecurityBindings
    {
        return new ModuleSecurityBindings(
            Cipher::AES_256_GCM,
            SecretKeys::CoreDataModule
        );
    }
}