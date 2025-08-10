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
 * Class BruteForceTable
 * @package App\Shared\Foundation\CoreData\BruteForceControl
 */
class BruteForceTable extends AbstractOrmTable
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::BFC, entityClass: null);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->string("action")->length(64);
        $cols->string("actor")->length(45);
        $cols->int("timestamp")->bytes(4)->unSigned();
        $cols->setPrimaryKey("id");

        $constraints->addIndexComposite("idx_action_actor_timestamp")
            ->columns("action", "actor", "timestamp");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}