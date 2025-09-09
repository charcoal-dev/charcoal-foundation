<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http;

use App\Shared\CharcoalApp;
use App\Shared\Enums\Http\HttpLogLevel;
use App\Shared\Foundation\Http\CallLog\CallLogSnapshot;
use App\Shared\Foundation\Http\ProxyServers\ProxyServer;
use Charcoal\App\Kernel\Contracts\Domain\ModuleBindableInterface;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\App\Kernel\Domain\AbstractModule;
use Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException;
use Charcoal\App\Kernel\Support\DtoHelper;
use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Http\Client\ClientConfig;
use Charcoal\Http\Client\Contracts\RequestObserverInterface;
use Charcoal\Http\Client\HttpClient;
use Charcoal\Http\Client\Request;
use Charcoal\Http\Client\Response;
use Charcoal\Http\Commons\Contracts\HeadersInterface;
use Charcoal\Http\Commons\Contracts\PayloadInterface;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Vectors\Support\DsvTokens;

/**
 * This class is responsible for sending HTTP requests with various configurations and behaviors.
 * It includes support for proxy configurations, SSL certificates, custom timeouts, and user agents.
 * The class also implements module binding and relies on component configuration resolution.
 * NOTE: This service is designed to be used with *multiple* HTTP clients.
 * @property HttpModule $module
 */
final class HttpService implements ModuleBindableInterface, RequestObserverInterface
{
    use NotSerializableTrait;
    use NotCloneableTrait;
    use NoDumpTrait;

    protected HttpClient $client;
    /** @var array<string, ProxyServer> */
    protected array $proxyConfigs;

    public function __construct(CharcoalApp $app)
    {
        $this->client = new HttpClient($app->config->httpClient);
        $this->proxyConfigs = [];
    }


    public function bootstrap(AbstractModule $module): void
    {
    }

    /**
     * @throws \Charcoal\Http\Client\Exceptions\RequestException
     */
    public function request(
        HttpMethod                  $method,
        string                      $url,
        HeadersInterface|array|null $headers = null,
        PayloadInterface|array|null $payload = null,
        HttpLogLevel                $logLevel = HttpLogLevel::None,
    ): Request
    {
        return (new Request(
            $this->client->config(),
            $method,
            $url,
            $headers,
            $payload))->observe($this, [$logLevel]);
    }

    /**
     * @throws \Charcoal\Http\Client\Exceptions\RequestException
     */
    public function proxy(
        ProxyServer                 $proxy,
        HttpMethod                  $method,
        string                      $url,
        HeadersInterface|array|null $headers = null,
        PayloadInterface|array|null $payload = null,
        HttpLogLevel                $logLevel = HttpLogLevel::None,
        ?DsvTokens                  $flags = null,
    ): Request
    {
        return (new Request(
            $this->getProxyConfig($proxy),
            $method,
            $url,
            $headers,
            $payload))->observe($this, [$logLevel, $proxy, $flags]);
    }

    /**
     * @param ProxyServer $proxy
     * @return ClientConfig
     */
    protected function getProxyConfig(ProxyServer $proxy): ClientConfig
    {
        if (isset($this->proxyConfigs[$proxy->uniqId])) {
            return $this->proxyConfigs[$proxy->uniqId];
        }

        return $this->proxyConfigs[$proxy->uniqId] = new ClientConfig(
            proxyServer: new \Charcoal\Http\Client\Proxy\ProxyServer(
                $proxy->hostname,
                $proxy->port,
                $proxy->authType === "basic" ? $proxy->authUsername : null,
                $proxy->authType === "basic" ? $proxy->authPassword : null
            ),
            previous: $this->client->config());
    }

    /**
     * @throws EntityRepositoryException
     */
    public function onRequestResult(Request $request, \Throwable|Response $result, array $context): void
    {
        // Log Level
        $logLevel = $context[0];
        if (!$logLevel instanceof HttpLogLevel) {
            Diagnostics::app()->warning("HTTP request " . $request->method->value . " " .
                $request->url->host . " observed without proper context [0]");
            return;
        }

        if ($logLevel !== HttpLogLevel::None) {
            return;
        }

        // Proxy Server?
        $proxyServer = $context[1] ?? null;
        if (!is_null($proxyServer) && !$proxyServer instanceof ProxyServer) {
            Diagnostics::app()->warning("HTTP request " . $request->method->value . " " .
                $request->url->host . " observed without proper context [1]",
                context: [
                    "logLevel" => $logLevel
                ]);
            return;
        }

        // Flags
        $flags = $context[2] ?? null;
        if (!is_null($flags) && !$flags instanceof DsvTokens) {
            Diagnostics::app()->warning("HTTP request " . $request->method->value . " " .
                $request->url->host . " observed without proper context [2]",
                context: [
                    "logLevel" => $logLevel,
                    "proxyServer" => $proxyServer?->uniqId
                ]);
            return;
        }

        $callLog = $this->module->callLog->createLog($request, $proxyServer, $flags);
        $callLogSnapshot = new CallLogSnapshot($callLog, $request, $logLevel);

        if ($result instanceof \Throwable) {
            $callLogSnapshot->exception = DtoHelper::getExceptionObject($result);
        }

        $this->module->callLog->finalizeCallLog(
            $callLog,
            $callLogSnapshot,
            $result instanceof Response ? $result : null,
            microtime(true),
            $logLevel
        );
    }
}