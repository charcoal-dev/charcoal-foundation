<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http\ProxyServers;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Http\HttpLogLevel;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Http\Commons\Enums\HttpMethod;

/**
 * Provides methods for handling and managing HTTP proxies within the ORM repository.
 * @property HttpModule $module
 */
final class ProxiesHandler extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(DatabaseTables::HttpProxies);
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function get(string $uniqId, bool $useCache): ProxyServer
    {
        /** @var ProxyServer */
        return $this->getEntity($uniqId, $useCache, "`uniq_id`=?", [$uniqId], $useCache);
    }

    /**
     * @param ProxyServer $proxyServer
     * @return void
     * @throws \Charcoal\Http\Client\Exceptions\HttpClientException
     * @api
     */
    public function testConnection(ProxyServer $proxyServer): void
    {
        $proxyServer->type->verifyConnection(
            $this->module->app->http->client()->request(
                HttpMethod::GET,
                sprintf("%s://%s%s",
                    $proxyServer->ssl ? "https" : "http",
                    $proxyServer->hostname,
                    ($proxyServer->port ? (":" . $proxyServer->port) : "")),
                logLevel: HttpLogLevel::Headers
            )->send());
    }
}