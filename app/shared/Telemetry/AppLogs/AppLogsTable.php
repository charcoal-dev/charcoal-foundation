<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\AppLogs;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Interfaces;
use App\Shared\Telemetry\TelemetryModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * This class represents the database table structure and migrations for application logs.
 * It extends the OrmTableBase class and defines the schema and constraints for the AppLogs table.
 */
final class AppLogsTable extends OrmTableBase
{
    public function __construct(TelemetryModule $module)
    {
        parent::__construct($module, DatabaseTables::AppLogs, AppLogEntity::class);
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
        $cols->enumObject("level", AppLogLevel::class)->options(...AppLogLevel::getCaseValues());
        $cols->string("message")->length(255);
        $cols->json("context")->nullable();
        $cols->json("exception")->nullable();
        $cols->int("logged_at")->size(4);
        $cols->setPrimaryKey("id");

        $constraints->addIndexComposite("idx_if_level_ts")->columns("interface", "level", "logged_at");
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