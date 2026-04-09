<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\EngineLog;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Telemetry\TelemetryModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * @property TelemetryModule $module
 */
final class EngineMetricsTable extends OrmTableBase
{
    public function __construct(TelemetryModule $module)
    {
        parent::__construct($module, DatabaseTables::EngineMetrics, entityClass: null);
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
        $cols->int("log_id")->size(8)->unSigned()->nullable();
        $cols->enumObject("state", ExecutionState::class)->options(...ExecutionState::getCaseValues());
        $cols->float("logged_at")->precision(16, 6)->unSigned();
        $cols->int("memory_usage")->size(8)->unSigned();
        $cols->int("memory_usage_peak")->size(8)->unSigned();
        $cols->int("cpu_time_user")->size(8)->unSigned();
        $cols->int("cpu_time_system")->size(8)->unSigned();
        $cols->int("cpu_time_total")->size(8)->unSigned();
        $cols->setPrimaryKey("id");

        $constraints->foreignKey("log_id")->table(DatabaseTables::EngineLogs->value, "id");
        $constraints->addIndexComposite("idx_log_time")->columns("log_id", "logged_at");
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