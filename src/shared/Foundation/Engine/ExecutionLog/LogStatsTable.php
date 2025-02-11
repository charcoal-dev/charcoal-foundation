<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine\ExecutionLog;

use App\Shared\AppDbTables;
use App\Shared\Core\Cli\CliScriptState;
use App\Shared\Foundation\Engine\EngineModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class LogStatsTable
 * @package App\Shared\Foundation\Engine\ExecutionLog
 */
class LogStatsTable extends AbstractOrmTable
{
    /**
     * @param EngineModule $module
     */
    public function __construct(EngineModule $module)
    {
        parent::__construct($module, AppDbTables::ENGINE_EXEC_STATS, entityClass: null);
    }

    /**
     * @param Columns $cols
     * @param Constraints $constraints
     * @return void
     */
    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->int("log")->bytes(8)->unSigned();
        $cols->enum("state")->options(...CliScriptState::getOptions());
        $cols->int("cpu_load")->bytes(2)->unSigned();
        $cols->int("memory_usage")->bytes(8)->unSigned();
        $cols->int("memory_usage_real")->bytes(8)->unSigned();
        $cols->int("peak_memory_usage")->bytes(8)->unSigned();
        $cols->int("peak_memory_usage_real")->bytes(8)->unSigned();
        $cols->double("timestamp")->precision(14, 4)->unSigned();
        $cols->setPrimaryKey("id");

        $constraints->foreignKey("log")->table(AppDbTables::ENGINE_EXEC_LOG->value, "id");
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }

    /**
     * @param ExecutionLogEntity $log
     * @param bool $upsert
     * @return void
     * @throws \Charcoal\Database\Exception\QueryExecuteException
     * @throws \Charcoal\Database\ORM\Exception\OrmQueryException
     */

}