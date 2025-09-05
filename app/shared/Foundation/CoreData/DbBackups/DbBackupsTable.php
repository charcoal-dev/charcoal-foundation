<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\DbBackups;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * This class defines the structure, constraints, and primary key for the DB_BACKUPS table.
 * It supports operations for managing metadata about backups, such as whether the backup
 * was automatic, its timestamp, filename, and size.
 * @property CoreDataModule $module
 */
final class DbBackupsTable extends OrmTableBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::DatabaseBackups, entityClass: DbBackupEntity::class);
    }

    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
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