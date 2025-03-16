<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\InterfaceLog;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\Http\HttpInterface;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;
use Charcoal\Http\Commons\HttpMethod;

/**
 * Class InterfaceLogTable
 * @package App\Shared\Foundation\Http\InterfaceLog
 * @property HttpModule $module
 */
class InterfaceLogTable extends AbstractOrmTable
{
    /**
     * @param HttpModule $module
     */
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, AppDbTables::HTTP_INTERFACE_LOG, InterfaceLogEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->enumObject("interface", HttpInterface::class)->options(...HttpInterface::getOptions());
        $cols->string("ip_address")->length(45);
        $cols->enumObject("method", HttpMethod::class)->options("GET", "POST", "PUT", "DELETE", "OPTIONS");
        $cols->string("endpoint")->length(255);
        $cols->double("start_on")->precision(14, 4)->unSigned();
        $cols->double("end_on")->precision(14, 4)->unSigned()->nullable();
        $cols->int("response_code")->bytes(2)->unSigned()->nullable();
        $cols->int("error_count")->bytes(2)->unSigned()->nullable();
        $cols->int("flag_sid")->bytes(8)->unSigned()->nullable();
        $cols->int("flag_uid")->bytes(8)->unSigned()->nullable();
        $cols->int("flag_tid")->bytes(8)->unSigned()->nullable();
        $cols->blobBuffer("snapshot")->size("medium")->nullable();
        $cols->setPrimaryKey("id");

        $constraints->addIndexComposite("idx_interface_method")->columns("interface", "method");
        $constraints->addIndex("start_on");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}