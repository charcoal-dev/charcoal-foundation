<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\HttpIngress;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Interfaces;
use App\Shared\Telemetry\TelemetryModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Represents the table definition and structure for HTTP ingress logging data.
 * Extends the OrmTableBase to inherit basic table operations and properties.
 */
final class HttpIngressTable extends OrmTableBase
{
    public function __construct(TelemetryModule $module)
    {
        parent::__construct($module, DatabaseTables::HttpIngress, HttpIngressLogEntity::class);
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
        $cols->string("uuid")->length(40)->nullable();
        $cols->string("ip_address")->length(45);
        $cols->int("response_code")->size(2)->unSigned()->nullable();
        $cols->string("method")->length(10);
        $cols->enum("url_scheme")->options("http", "https");
        $cols->string("url_host")->length(100);
        $cols->int("url_port")->size(2)->unSigned()->nullable();
        $cols->string("url_path")->length(255);
        $cols->string("controller")->length(100)->nullable();
        $cols->string("entrypoint")->length(40)->nullable();
        $cols->json("request_headers")->nullable();
        $cols->json("request_params_query")->nullable();
        $cols->json("request_params_body")->nullable();
        $cols->json("response_headers")->nullable();
        $cols->json("response_params")->nullable();
        $cols->int("flag_sid")->size(8)->unSigned()->nullable();
        $cols->int("flag_uid")->size(8)->unSigned()->nullable();
        $cols->int("flag_tid")->size(8)->unSigned()->nullable();
        $cols->int("logged_at")->size(4)->unSigned();
        $cols->int("duration")->size(8)->unSigned()->nullable();
        $cols->setPrimaryKey("id");
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}