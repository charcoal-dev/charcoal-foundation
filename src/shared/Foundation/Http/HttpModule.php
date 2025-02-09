<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\Http\CallLog\CallLogHandler;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogHandler;
use App\Shared\Foundation\Http\ProxyServers\ProxyServersOrm;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\Cipher\Cipher;

/**
 * Class HttpModule
 * @package App\Shared\Foundation\Http
 */
class HttpModule extends AppOrmModule
{
    public CallLogHandler $callLog;
    public InterfaceLogHandler $requestLog;
    public ProxyServersOrm $proxyServers;
    public HttpClient $client;

    /**
     * @param AppBuildPartial $app
     * @param Http[] $components
     */
    public function __construct(AppBuildPartial $app, array $components)
    {
        parent::__construct($app, CacheStore::PRIMARY, $components);
    }

    public function getCipher(AbstractModuleComponent $resolveFor): ?Cipher
    {
    }

    protected function includeComponent(Http|ModuleComponentEnum $component, AppBuildPartial $app): bool
    {
        switch ($component) {
            default:
                return false;
        }
    }

    protected function createDbTables(Http|ModuleComponentEnum $component, DatabaseTableRegistry $tables): bool
    {
        switch ($component) {
            default:
                return false;
        }
    }
}