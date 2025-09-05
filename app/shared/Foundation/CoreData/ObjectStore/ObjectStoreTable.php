<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Represents the database table for storing objects in the application.
 * @property CoreDataModule $module
 */
final class ObjectStoreTable extends OrmTableBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::ObjectStore, entityClass: null);
    }

    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
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