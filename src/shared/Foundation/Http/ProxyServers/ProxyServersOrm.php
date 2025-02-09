<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\ProxyServers;

use App\Shared\AppDbTables;
use App\Shared\Foundation\Http\HttpLogLevel;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\HTTP\Client\Request;
use Charcoal\HTTP\Commons\HttpMethod;

/**
 * Class ProxyServersOrm
 * @package App\Shared\Foundation\Http\ProxyServers
 * @property HttpModule $module
 */
class ProxyServersOrm extends AbstractOrmRepository
{
    /**
     * @param HttpModule $module
     */
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, AppDbTables::HTTP_PROXIES);
    }

    /**
     * @param string $uniqId
     * @param bool $useCache
     * @return HttpProxy
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function get(string $uniqId, bool $useCache): HttpProxy
    {
        /** @var HttpProxy */
        return $this->getEntity($uniqId, $useCache, "`uniq_id`=?", [$uniqId], $useCache);
    }

    /**
     * @param HttpProxy $proxyServer
     * @return void
     * @throws \Charcoal\HTTP\Client\Exception\RequestException
     * @throws \Throwable
     */
    public function testConnection(HttpProxy $proxyServer): void
    {
        $requestUrl = ($proxyServer->ssl ? "https" : "http") . "://"
            . $proxyServer->hostname
            . ($proxyServer->port ? (":" . $proxyServer->port) : "");

        $proxyServer->type->verifyConnection($this->module->client->send(
            new Request(HttpMethod::GET, $requestUrl),
            HttpLogLevel::HEADERS
        ));
    }
}