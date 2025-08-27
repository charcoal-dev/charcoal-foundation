<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\ProxyServers;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Http\ProxyType;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Base\Enums\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Represents a database table for proxy server configurations with defined structure and constraints.
 * Extends the OrmTableBase for database table manipulation functionality.
 * @property HttpModule $module
 */
final class ProxiesTable extends OrmTableBase
{
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, DatabaseTables::HttpProxies, entityClass: ProxyServer::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->string("uniq_id")->length(12)->unique();
        $cols->bool("status")->default(false);
        $cols->enumObject("type", ProxyType::class)->options(...ProxyType::getCases());
        $cols->string("hostname")->length(45);
        $cols->int("port")->bytes(2)->unSigned()->nullable();
        $cols->bool("ssl")->default(false);
        $cols->string("ssl_ca_path")->length(128)->nullable();
        $cols->enum("auth_type")->options("na", "basic")->default("na");
        $cols->string("auth_username")->length(64)->nullable();
        $cols->string("auth_password")->length(64)->nullable();
        $cols->int("timeout")->bytes(1)->unSigned()->default(1);
        $cols->int("updated_on")->bytes(4)->unSigned();

        $constraints->uniqueKey("uniq_host")->columns("hostname", "port");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}