<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\CipherKey;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\Http\CallLog\CallLogHandler;
use App\Shared\Foundation\Http\CallLog\CallLogTable;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogHandler;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogTable;
use App\Shared\Foundation\Http\ProxyServers\HttpProxy;
use App\Shared\Foundation\Http\ProxyServers\ProxyServersOrm;
use App\Shared\Foundation\Http\ProxyServers\ProxyServersTable;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\Cipher\Cipher;
use Charcoal\Http\Client\Request;
use Charcoal\Http\Client\Response;
use Charcoal\OOP\Vectors\DsvString;

/**
 * Class HttpModule
 * @package App\Shared\Foundation\Http
 */
class HttpModule extends AppOrmModule
{
    public CallLogHandler $callLog;
    public InterfaceLogHandler $interfaceLog;
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

    /**
     * @param AppBuildPartial $app
     * @return void
     */
    protected function declareChildren(AppBuildPartial $app): void
    {
        parent::declareChildren($app);
        $this->client = new HttpClient($this);
    }

    /**
     * @param Request $request
     * @param HttpLogLevel $logLevel
     * @param DsvString|null $flags
     * @param HttpProxy|null $proxyServer
     * @param bool $useTimeouts
     * @param bool $useCertAuthFile
     * @param bool $useUserAgent
     * @return Response
     * @throws \Throwable
     */
    public function sendRequest(
        Request      $request,
        HttpLogLevel $logLevel = HttpLogLevel::NONE,
        ?DsvString   $flags = null,
        ?HttpProxy   $proxyServer = null,
        bool         $useTimeouts = true,
        bool         $useCertAuthFile = true,
        bool         $useUserAgent = true,
    ): Response
    {
        return $this->client->send(
            $request,
            $logLevel,
            $flags,
            $proxyServer,
            $useTimeouts,
            $useCertAuthFile,
            $useUserAgent
        );
    }

    /**
     * @param AbstractModuleComponent $resolveFor
     * @return Cipher
     */
    public function getCipher(AbstractModuleComponent $resolveFor): Cipher
    {
        return $this->app->cipher->get(CipherKey::PRIMARY);
    }

    /**
     * @param Http|ModuleComponentEnum $component
     * @param AppBuildPartial $app
     * @return bool
     */
    protected function includeComponent(Http|ModuleComponentEnum $component, AppBuildPartial $app): bool
    {
        switch ($component) {
            case Http::CALL_LOG:
                $this->callLog = new CallLogHandler($this);
                return true;
            case Http::INTERFACE_LOG:
                $this->interfaceLog = new InterfaceLogHandler($this);
                return true;
            case Http::PROXY_SERVERS:
                $this->proxyServers = new ProxyServersOrm($this);
                return true;
            default:
                return false;
        }
    }

    /**
     * @param Http|ModuleComponentEnum $component
     * @param DatabaseTableRegistry $tables
     * @return bool
     */
    protected function createDbTables(Http|ModuleComponentEnum $component, DatabaseTableRegistry $tables): bool
    {
        switch ($component) {
            case Http::CALL_LOG:
                $tables->register(new CallLogTable($this));
                return true;
            case Http::INTERFACE_LOG:
                $tables->register(new InterfaceLogTable($this));
                return true;
            case Http::PROXY_SERVERS:
                $tables->register(new ProxyServersTable($this));
                return true;
            default:
                return false;
        }
    }
}