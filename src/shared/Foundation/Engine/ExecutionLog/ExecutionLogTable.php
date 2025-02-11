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
 * Class ExecutionLogTable
 * @package App\Shared\Foundation\Engine\ExecutionLog
 */
class ExecutionLogTable extends AbstractOrmTable
{
    public function __construct(EngineModule $module)
    {
        parent::__construct($module, AppDbTables::ENGINE_EXEC_LOG, ExecutionLogEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->string("script")->length(40);
        $cols->string("label")->length(80)->nullable();
        $cols->enumObject("state", CliScriptState::class)->options(...CliScriptState::getOptions());
        $cols->blobBuffer("context")->size("medium");
        $cols->int("pid")->bytes(4)->unSigned();
        $cols->double("started_on")->precision(14, 4)->unSigned();
        $cols->double("updated_on")->precision(14, 4)->unSigned()->nullable();
        $cols->setPrimaryKey("id");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}