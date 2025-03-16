<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\SystemAlerts;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class SystemAlertsTable
 * @package App\Shared\Foundation\CoreData\SystemAlerts
 */
class SystemAlertsTable extends AbstractOrmTable
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::SYSTEM_ALERTS, SystemAlertEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->enumObject("level", SystemAlertLevel::class)->options(...SystemAlertLevel::getOptions());
        $cols->string("message")->length(255);
        $cols->int("timestamp")->bytes(4)->unSigned();
        $cols->enum("trace_interface")->options("http", "cli")->nullable();
        $cols->int("trace_qid")->bytes(8)->unSigned()->nullable();
        $cols->blobBuffer("context")->nullable();
        $cols->setPrimaryKey("id");

        $constraints->addIndex("trace_interface");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}