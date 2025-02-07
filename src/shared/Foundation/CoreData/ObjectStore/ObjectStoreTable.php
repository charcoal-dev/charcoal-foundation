<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\ObjectStore;

use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\App\Kernel\Orm\Db\DbAwareTableEnum;
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
     * @param DbAwareTableEnum $dbTableEnum
     */
    public function __construct(CoreDataModule $module, DbAwareTableEnum $dbTableEnum)
    {
        parent::__construct($module, $dbTableEnum, entityClass: null);
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