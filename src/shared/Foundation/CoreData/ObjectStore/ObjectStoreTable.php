<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Base\Enums\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Represents the database table for storing objects in the application.
 * @property CoreDataModule $module
 */
class ObjectStoreTable extends OrmTableBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::ObjectStore, entityClass: null);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->string("key")->length(40)->unique();
        $cols->binary("data_blob")->length(10240);
        $cols->string("match_rule")->length(80)->nullable();
        $cols->int("timestamp")->bytes(4)->unSigned();
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}