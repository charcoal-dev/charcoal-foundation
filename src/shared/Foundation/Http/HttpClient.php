<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\AbstractComponentConfig;
use App\Shared\Core\Config\ComponentConfigResolverTrait;
use App\Shared\Foundation\Http\CallLog\CallLogSnapshot;
use App\Shared\Foundation\Http\Config\HttpClientConfig;
use App\Shared\Foundation\Http\ProxyServers\HttpProxy;
use Charcoal\App\Kernel\Errors;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\App\Kernel\Orm\Exception\NoChangesException;
use Charcoal\HTTP\Client\Request;
use Charcoal\HTTP\Client\Response;
use Charcoal\OOP\Vectors\DsvString;

/**
 * Class HttpClient
 * @package App\Shared\Foundation\Http\Client
 * @property HttpModule $module
 */
class HttpClient extends AbstractModuleComponent
{
    private ?HttpClientConfig $config = null;

    use ComponentConfigResolverTrait;

    /**
     * @param HttpModule $module
     */
    public function __construct(HttpModule $module)
    {
        parent::__construct($module);
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["config"] = null;
        return $data;
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
    public function send(
        Request      $request,
        HttpLogLevel $logLevel = HttpLogLevel::BASIC,
        ?DsvString   $flags = null,
        ?HttpProxy   $proxyServer = null,
        bool         $useTimeouts = true,
        bool         $useCertAuthFile = true,
        bool         $useUserAgent = true,
    ): Response
    {
        $this->resolveConfig();

        // Use Configured UserAgent?
        if ($useUserAgent) {
            $request->userAgent($this->config->userAgent);
        }

        if ($proxyServer) {
            $request->useProxy($proxyServer->hostname, $proxyServer->port);
            if ($proxyServer->authType !== "na") {
                if ($proxyServer->authType === "basic") {
                    $request->useProxyCredentials($proxyServer->authUsername ?? "", $proxyServer->authPassword ?? "");
                }

                if ($proxyServer->authType !== "basic") {
                    throw new \LogicException(
                        sprintf("No logic implemented for HttpProxy %s authType", $proxyServer->uniqId));
                }
            }

            // Proxy server timeouts
            $request->setTimeouts($proxyServer->timeout, $proxyServer->timeout);

            // Proxy server SSL
            if ($proxyServer->ssl) {
                $request->ssl()->certificateAuthority($proxyServer->sslCaPath ?? $this->config->sslCertificateFilePath);
            }

            return $this->sendHttpRequest($request, $logLevel, $proxyServer, $flags);
        }

        // Use Configured Timeouts?
        if ($useTimeouts) {
            $request->setTimeouts($this->config->timeout, $this->config->connectTimeout);
        }

        // Use Configured SSL CA filepath?
        if ($useCertAuthFile) {
            $request->ssl()->certificateAuthority($this->config->sslCertificateFilePath);
        }

        return $this->sendHttpRequest($request, $logLevel, null, $flags);
    }

    /**
     * @param Request $request
     * @param HttpLogLevel $logLevel
     * @param HttpProxy|null $proxyServer
     * @param DsvString|null $flags
     * @return Response
     * @throws \Throwable
     */
    private function sendHttpRequest(
        Request      $request,
        HttpLogLevel $logLevel,
        ?HttpProxy   $proxyServer = null,
        ?DsvString   $flags = null
    ): Response
    {
        $callLog = null;
        $callLogSnapshot = new CallLogSnapshot($request, $logLevel);
        if ($logLevel !== HttpLogLevel::NONE) {
            if (!isset($this->module->callLog)) {
                throw new \LogicException("HttpModule does not have CallLog component built");
            }

            $callLog = $this->module->callLog->createLog($request, $proxyServer, $flags);
            $callLogSnapshot->callId = $callLog->id;
        }

        try {
            $response = $request->send();
        } catch (\Throwable $t) {
            $callLogSnapshot->exception = Errors::Exception2Array($t);
            $httpClientError = $t;
        }

        if ($callLog) {
            try {
                $this->module->callLog->finaliseCallLog(
                    $callLog,
                    $callLogSnapshot,
                    $response ?? null,
                    microtime(true),
                    $logLevel
                );
            } catch (NoChangesException) {
            }
        }

        if (isset($httpClientError)) {
            throw $httpClientError;
        }

        return $response ?? throw new \RuntimeException("Unexpected state: no response was generated");
    }

    /**
     * @return void
     */
    protected function resolveConfig(): void
    {
        if ($this->config) {
            return;
        }

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->config = $this->resolveConfigObject(
            $this->module->app,
            HttpClientConfig::class,
            useStatic: true,
            useObjectStore: false
        );
    }

    /**
     * @param CharcoalApp $app
     * @return AbstractComponentConfig|null
     */
    protected function resolveStaticConfig(CharcoalApp $app): ?AbstractComponentConfig
    {
        if (isset($app->config->http->clientConfig)) {
            return $app->config->http->clientConfig;
        }

        return null;
    }
}