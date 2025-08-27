<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\BruteForceControl;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Represents the table structure and properties for managing brute force tracking data.
 * @property CoreDataModule $module
 */
final class BruteForceTable extends OrmTableBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::BruteForceControl, entityClass: null);
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