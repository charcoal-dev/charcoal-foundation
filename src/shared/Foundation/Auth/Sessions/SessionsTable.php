<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Auth\Sessions;

use App\Shared\AppDbTables;
use App\Shared\Contracts\Accounts\AccountRealm;
use App\Shared\Foundation\Auth\AuthModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class SessionsTable
 * @package App\Shared\Foundation\Auth\Sessions
 * @property AuthModule $module
 */
class SessionsTable extends AbstractOrmTable
{
    /**
     * @param AuthModule $module
     */
    public function __construct(AuthModule $module)
    {
        parent::__construct($module, AppDbTables::AUTH_SESSIONS, SessionEntity::class);
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
        $cols->binaryFrame("checksum")->fixed(20);
        $cols->enumObject("realm", AccountRealm::class)->options(...AccountRealm::getOptions());
        $cols->enumObject("type", SessionType::class)->options(...SessionType::getOptions());
        $cols->bool("archived")->default(false);
        $cols->binaryFrame("token")->fixed(32)->unique();
        $cols->binaryFrame("device_fp")->fixed(32);
        $cols->binaryFrame("hmac_secret")->fixed(48);
        $cols->string("ip_address")->length(45);
        $cols->string("user_agent")->length(255);
        $cols->int("uid")->bytes(4)->unSigned();
        $cols->int("issued_on")->bytes(4)->unSigned();
        $cols->int("last_used_on")->bytes(4)->unSigned();
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