<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use App\Shared\AppDbTables;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;
use Charcoal\Http\Commons\HttpMethod;

/**
 * Class CallLogTable
 * @package App\Shared\Foundation\Http\CallLog
 * @property HttpModule $module
 */
class CallLogTable extends AbstractOrmTable
{
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, AppDbTables::HTTP_CALL_LOG, CallLogEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->string("proxy_id")->length(12)->nullable();
        $cols->dsvString("flags", ",")->limit(6)->length(255)->nullable();
        $cols->enumObject("method", HttpMethod::class)->options("GET", "POST", "PUT", "DELETE", "OPTIONS");
        $cols->string("url_server")->length(255);
        $cols->string("url_path")->length(255)->default("/");
        $cols->double("start_on")->precision(14, 4)->unSigned();
        $cols->double("end_on")->precision(14, 4)->unSigned()->nullable();
        $cols->int("response_code")->bytes(2)->unSigned()->nullable();
        $cols->int("response_length")->bytes(4)->unSigned()->nullable();
        $cols->blobBuffer("snapshot")->size("medium")->nullable();
        $cols->setPrimaryKey("id");

        $constraints->foreignKey("proxy_id")->table(AppDbTables::HTTP_PROXIES->value, "uniq_id");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}