<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\DbBackups;

use App\Shared\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class DbBackupsTable
 * @package App\Shared\Foundation\CoreData\DbBackups
 * @property CoreDataModule $module
 */
class DbBackupsTable extends AbstractOrmTable
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::DB_BACKUPS, entityClass: DbBackupEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(4)->unSigned()->autoIncrement();
        $cols->bool("is_auto");
        $cols->string("database")->length(40);
        $cols->int("timestamp")->bytes(4)->unSigned();
        $cols->string("filename")->length(80);
        $cols->int("size")->bytes(8)->unSigned();
        $cols->setPrimaryKey("id");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}