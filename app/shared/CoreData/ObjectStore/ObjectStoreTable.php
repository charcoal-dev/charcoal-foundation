<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\ObjectStore;

use App\Shared\CoreData\CoreDataModule;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Class ObjectStoreTable
 * @package App\Shared\CoreData\ObjectStore
 */
final class ObjectStoreTable extends OrmTableBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::ObjectStore, StoredObjectEntity::class);
    }

    /**
     * @param ColumnsBuilder $cols
     * @param ConstraintsBuilder $constraints
     * @return void
     */
    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->string("ref")->length(64);
        $cols->int("version")->unSigned()->size(2);
        $cols->binary("payload")->length(10240);
        $cols->string("kid")->length(80)->nullable();
        $cols->int("updated_on")->size(4)->unSigned();

        $constraints->uniqueKey("ref_id")->columns("ref", "version")->isPrimary();
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}