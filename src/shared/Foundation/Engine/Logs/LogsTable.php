<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine\Logs;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\Engine\EngineModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Base\Enums\Charset;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Database\Orm\Concerns\LobSize;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 */
class LogsTable extends OrmTableBase
{
    public function __construct(EngineModule $module)
    {
        parent::__construct($module, DatabaseTables::EngineExecLog, LogEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->string("script")->length(40);
        $cols->string("label")->length(80)->nullable();
        $cols->enumObject("state", ExecutionState::class)->options(...ExecutionState::getCaseValues());
        $cols->blobBuffer("context")->size(LobSize::MEDIUM);
        $cols->int("pid")->bytes(4)->unSigned();
        $cols->double("started_on")->precision(14, 4)->unSigned();
        $cols->double("updated_on")->precision(14, 4)->unSigned()->nullable();
        $cols->setPrimaryKey("id");

        $constraints->addIndex("script");
        $constraints->addIndex("state");
        $constraints->addIndex("updated_on");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}