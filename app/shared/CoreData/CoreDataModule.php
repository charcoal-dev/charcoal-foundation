<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData;

use App\Shared\CharcoalApp;
use App\Shared\CoreData\ObjectStore\ObjectStoreRepository;
use App\Shared\CoreData\ObjectStore\ObjectStoreTable;
use App\Shared\Traits\OrmModuleTrait;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;

/**
 * This class provides functionality for normalizing storage keys,
 * accessing cache storage, declaring database tables, resolving ciphers
 * for specific ORM repositories, and retrieving semaphore providers.
 */
final class CoreDataModule extends OrmModuleBase
{
    use OrmModuleTrait;

    public readonly ObjectStoreRepository $objectStore;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->objectStore = new ObjectStoreRepository();
    }

    /**
     * @param TableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new ObjectStoreTable($this));
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->objectStore = $data["objectStore"];
        $this->cipherKeyRef = $data["cipherKeyRef"];
        parent::__unserialize($data);
    }
}