<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData;

use App\Shared\CharcoalApp;
use App\Shared\Concerns\NormalizedStorageKeysTrait;
use App\Shared\Concerns\PendingModuleComponents;
use App\Shared\Enums\CacheStores;
use App\Shared\Foundation\CoreData\BruteForceControl\BruteForceLogger;
use App\Shared\Foundation\CoreData\BruteForceControl\BruteForceTable;
use App\Shared\Foundation\CoreData\Countries\CountriesRepository;
use App\Shared\Foundation\CoreData\Countries\CountriesTable;
use App\Shared\Foundation\CoreData\DbBackups\DbBackupService;
use App\Shared\Foundation\CoreData\DbBackups\DbBackupsTable;
use App\Shared\Foundation\CoreData\ObjectStore\ObjectStoreService;
use App\Shared\Foundation\CoreData\ObjectStore\ObjectStoreTable;
use Charcoal\App\Kernel\Contracts\Domain\AppBindableInterface;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cache\CacheClient;

/**
 * This class provides access to various data and storage components,
 * such as object store handling, country management, brute force logging,
 * and database backup operations.
 * @property-read CharcoalApp $app
 */
final class CoreDataModule extends OrmModuleBase implements AppBindableInterface
{
    use PendingModuleComponents;
    use NormalizedStorageKeysTrait;

    public readonly ObjectStoreService $objectStore;
    public readonly CountriesRepository $countries;
    public readonly BruteForceLogger $bruteForce;
    public readonly DbBackupService $dbBackups;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->objectStore = new ObjectStoreService($this);
        $this->countries = new CountriesRepository($this);
        $this->bruteForce = new BruteForceLogger($this);
        $this->dbBackups = new DbBackupService($this);
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->objectStore = $data["objectStore"];
        $this->countries = $data["countries"];
        $this->bruteForce = $data["bruteForce"];
        $this->dbBackups = $data["dbBackups"];
        parent::__unserialize($data);
    }

    /**
     * Registers the required database tables with the table registry.
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new BruteForceTable($this));
        $tables->register(new CountriesTable($this));
        $tables->register(new DbBackupsTable($this));
        $tables->register(new ObjectStoreTable($this));
    }

    /**
     * Retrieves the primary cache store instance.
     */
    public function getCacheStore(): ?CacheClient
    {
        return $this->app->cache->getStore(CacheStores::Primary);
    }
}