<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\ProxyServers;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class ProxyServersTable
 * @package App\Shared\Foundation\Http\ProxyServers
 */
class ProxyServersTable extends AbstractOrmTable
{
    /**
     * @param HttpModule $module
     */
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, AppDbTables::HTTP_PROXIES, entityClass: HttpProxy::class);
    }

    /**
     * @param Columns $cols
     * @param Constraints $constraints
     * @return void
     */
    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->string("uniq_id")->length(12)->unique();
        $cols->bool("status")->default(false);
        $cols->enumObject("type", ProxyType::class)->options(...ProxyType::getOptions());
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

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}