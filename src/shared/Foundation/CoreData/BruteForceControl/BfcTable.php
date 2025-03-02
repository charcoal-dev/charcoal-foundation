<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\BruteForceControl;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class BfcTable
 * @package App\Shared\Foundation\CoreData\BruteForceControl
 */
class BfcTable extends AbstractOrmTable
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::BFC, entityClass: null);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->string("action")->length(64);
        $cols->string("caller")->length(45);
        $cols->int("timestamp")->bytes(4)->unSigned();
        $cols->setPrimaryKey("id");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}