<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\Metrics;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Interfaces;
use App\Shared\Telemetry\TelemetryModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Represents the Metrics table structure and its migration logic.
 * Extends the base ORM table functionalities for the App Metrics database table.
 */
final class MetricsTable extends OrmTableBase
{
    public function __construct(TelemetryModule $module)
    {
        parent::__construct($module, DatabaseTables::AppMetrics, MetricsEntity::class);
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
        $cols->enumObject("interface", Interfaces::class)->options(...Interfaces::getCaseValues());
        $cols->enum("sapi")->options("http", "cli");
        $cols->string("uuid")->length(40)->nullable();
        $cols->int("logged_at")->size(4)->unSigned();
        $cols->int("memory_usage")->size(8)->unSigned();
        $cols->int("memory_usage_peak")->size(8)->unSigned();
        $cols->int("cpu_time_user")->size(8)->unSigned();
        $cols->int("cpu_time_system")->size(8)->unSigned();
        $cols->int("cpu_time_total")->size(8)->unSigned();
        $cols->setPrimaryKey("id");

        $constraints->addIndexComposite("idx_if_ts")->columns("interface", "logged_at");
        $constraints->addIndex("logged_at");
        $constraints->addIndex("uuid");
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}