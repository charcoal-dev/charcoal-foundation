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
final class EngineLogTable extends OrmTableBase
{
    public function __construct(TelemetryModule $module)
    {
        parent::__construct($module, DatabaseTables::EngineLogs, EngineLogEntity::class);
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
        $cols->enum("type")->options("script", "process");
        $cols->string("command")->length(40);
        $cols->string("label")->length(80)->nullable();
        $cols->int("pid")->size(4)->unSigned();
        $cols->enumObject("last_state", ExecutionState::class)->options(...ExecutionState::getCaseValues());
        $cols->json("flags")->nullable();
        $cols->json("arguments")->nullable();
        $cols->float("started_on")->precision(14, 6)->unSigned();
        $cols->float("updated_on")->precision(14, 6)->unSigned()->nullable();
        $cols->setPrimaryKey("id");

        $constraints->addIndexComposite("idx_type_start")->columns("type", "started_on");
        $constraints->addIndexComposite("idx_type_cmd_start")->columns("type", "command", "started_on");
        $constraints->addIndex("started_on");
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}