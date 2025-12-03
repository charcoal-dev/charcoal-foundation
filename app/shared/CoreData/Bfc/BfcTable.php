<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Bfc;

use App\Shared\CoreData\CoreDataModule;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Represents the table for brute force control in the database.
 * Provides structure definition and migration handling for the table.
 */
final class BfcTable extends OrmTableBase
{
    /**
     * @param CoreDataModule $module
     */
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::BruteForceControl, null);
    }

    /**
     * @param ColumnsBuilder $cols
     * @param ConstraintsBuilder $constraints
     * @return void
     */
    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->size(8)->unSigned()->autoIncrement();
        $cols->string("action")->length(64);
        $cols->string("actor")->length(45);
        $cols->int("logged_at")->size(4)->unSigned();
        $cols->setPrimaryKey("id");

        $constraints->addIndexComposite("idx_actor_action_ts")
            ->columns("actor", "action", "logged_at");
        $constraints->addIndex("logged_at");
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}