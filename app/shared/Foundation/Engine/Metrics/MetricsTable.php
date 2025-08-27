<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Engine\Metrics;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\Engine\EngineModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Base\Enums\Charset;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Represents the Metrics table in the database.
 * @property EngineModule $module
 */
final class MetricsTable extends OrmTableBase
{
    public function __construct(EngineModule $module)
    {
        parent::__construct($module, DatabaseTables::EngineExecMetrics, entityClass: null);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->int("log")->bytes(8)->unSigned();
        $cols->enum("state")->options(...ExecutionState::getCases());
        $cols->int("cpu_load")->bytes(2)->unSigned();
        $cols->int("memory_usage")->bytes(8)->unSigned();
        $cols->int("memory_usage_real")->bytes(8)->unSigned();
        $cols->int("peak_memory_usage")->bytes(8)->unSigned();
        $cols->int("peak_memory_usage_real")->bytes(8)->unSigned();
        $cols->double("timestamp")->precision(14, 4)->unSigned();
        $cols->setPrimaryKey("id");

        $constraints->foreignKey("log")->table(DatabaseTables::EngineExecMetrics->value, "id");

        $constraints->addIndex("log");
        $constraints->addIndex("timestamp");
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}