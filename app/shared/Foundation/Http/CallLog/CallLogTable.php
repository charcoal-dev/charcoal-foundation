<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Base\Enums\Charset;
use Charcoal\Database\Orm\Concerns\LobSize;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;
use Charcoal\Http\Commons\Enums\HttpMethod;

/**
 * This class defines the schema, constraints, foreign key relationships, and
 * other table-related configurations for storing HTTP call log entries.
 * @property HttpModule $module
 */
final class CallLogTable extends OrmTableBase
{
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, DatabaseTables::HttpCallLog, CallLogEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->string("proxy_id")->length(12)->nullable();
        $cols->dsvString("flags", ",")->length(255)->nullable();
        $cols->enumObject("method", HttpMethod::class)->options("GET", "POST", "PUT", "DELETE", "OPTIONS");
        $cols->string("url_server")->length(255);
        $cols->string("url_path")->length(255)->default("/");
        $cols->double("start_on")->precision(14, 4)->unSigned();
        $cols->double("end_on")->precision(14, 4)->unSigned()->nullable();
        $cols->int("response_code")->bytes(2)->unSigned()->nullable();
        $cols->int("response_length")->bytes(4)->unSigned()->nullable();
        $cols->blobBuffer("snapshot")->size(LobSize::MEDIUM)->nullable();
        $cols->setPrimaryKey("id");

        $constraints->foreignKey("proxy_id")->database(DatabaseTables::HttpProxies->getDatabase()->getConfigKey())
            ->table(DatabaseTables::HttpProxies->value, "uniq_id");

        $constraints->addIndex("proxy_id");
        $constraints->addIndex("method");
        $constraints->addIndex("start_on");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}