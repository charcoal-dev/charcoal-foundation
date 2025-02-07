<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

use App\Shared\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class ObjectStoreTable
 * @package App\Shared\Foundation\CoreData\ObjectStore
 */
class ObjectStoreTable extends AbstractOrmTable
{
    /**
     * @param CoreDataModule $module
     */
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::OBJECT_STORE, entityClass: null);
    }

    /**
     * @param Columns $cols
     * @param Constraints $constraints
     * @return void
     */
    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->string("key")->length(40)->unique();
        $cols->binary("data_blob")->length(10240);
        $cols->string("match_rule")->length(80)->nullable();
        $cols->int("timestamp")->bytes(4)->unSigned();
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}