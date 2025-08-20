<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Context\Api\Errors\GatewayError;
use App\Shared\Core\Http\Cache\ResponseCache;
use App\Shared\Core\Http\Exception\Cache\ResponseFromCacheException;
use App\Shared\Core\Http\Request\Policy\Auth\AuthAwareRouteInterface;
use App\Shared\Core\Http\Request\Policy\Auth\AuthContextInterface;
use App\Shared\Core\Http\Request\Policy\Concurrency\ConcurrencyEnforcer;
use App\Shared\Core\Http\Request\Policy\Concurrency\ConcurrencyPolicy;
use App\Shared\Core\Http\Request\Policy\Cors\CorsBinding;
use App\Shared\Core\Http\Request\Policy\Cors\CorsHeaders;
use App\Shared\Core\Http\Request\Policy\DeviceFingerprintRequiredRoute;
use App\Shared\Enums\Http\CorsPolicy;
use App\Shared\Enums\Http\HttpLogLevel;
use App\Shared\Enums\SemaphoreScopes;
use App\Shared\Exceptions\ApiValidationException;
use App\Shared\Exceptions\Http\ConcurrentHttpRequestException;
use App\Shared\Exceptions\Http\CorsOriginMismatchException;
use App\Shared\Foundation\Http\InterfaceLog\LogEntity;
use App\Shared\Foundation\Http\InterfaceLog\RequestSnapshot;
use App\Shared\Utility\NetworkHelper;
use App\Shared\Utility\StringHelper;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\App\Kernel\EntryPoint\Http\AbstractRouteController;
use Charcoal\App\Kernel\Errors;
use Charcoal\Buffers\AbstractFixedLenBuffer;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Router\Contracts\Response\ResponseResolvedInterface;
use Charcoal\Http\Router\Enums\CacheStoreDirective;
use Charcoal\Http\Router\Response\AbstractResponse;
use Charcoal\Semaphore\Filesystem\FileLock;

/**
 * Class AppAwareEndpoint
 * @package App\Shared\Core\Http
 * @property CharcoalApp $app
 */
abstract class AbstractAppEndpoint extends AbstractRouteController
{
    protected const array LOG_EXCLUDE_REQUEST_HEADERS = [];
    protected const array LOG_EXCLUDE_RESPONSE_HEADERS = [];
    protected const array LOG_EXCLUDE_REQUEST_PARAMS = [];
    protected const array LOG_EXCLUDE_RESPONSE_PARAMS = [];

    public readonly string $userIpAddress;
    public readonly ?HttpInterfaceProfile $interface;
    public readonly ?AbstractFixedLenBuffer $deviceFp;
    protected readonly ?AuthContextInterface $authContext;

    public readonly HttpLogLevel $requestLogLevel;
    protected readonly ?LogEntity $requestLog;
    protected readonly ?RequestSnapshot $requestLogSnapshot;

    protected readonly ?CorsBinding $corsBinding;
    protected readonly ?ConcurrencyPolicy $concurrencyPolicy;
    private ?FileLock $concurrencyLock = null;

    protected bool $exceptionReturnTrace = false;
    protected bool $exceptionFullClassname = false;
    protected bool $exceptionIncludePrevious = false;

    /**
     * @throws ApiValidationException
     * @throws CorsOriginMismatchException
     */
    final protected function delegateResolveEntrypoint(): callable
    {
        // User IP Address
        $this->userIpAddress = $this->userClient->cfConnectingIP ??
            $this->userClient->xForwardedFor ??
            $this->userClient->ipAddress;

        if (!NetworkHelper::isValidIpAddress($this->userIpAddress, true, true)) {
            throw new \UnexpectedValueException("Invalid remote IP address");
        }

        // Interface Configuration
        $this->interface = $this->declareHttpInterface();
        $this->deviceFp = $this instanceof DeviceFingerprintRequiredRoute ? $this->resolveDeviceFp() : null;
        $this->corsBinding = $this->declareCorsBinding();
        $this->concurrencyPolicy = $this->declareconcurrencyPolicy();

        // CORS Binding
        $this->corsBinding->validateOrigin($this);

        // Interface Status
        if (!$this->interface->config->status) {
            $this->handleInterfaceIsDisabled();
        }

        // Terminate
        return $this->resolveEntryPointMethod();
    }

    abstract protected function resolveEntryPointMethod(): callable;

    abstract protected function appEndpointCallback(): void;

    abstract protected function declareHttpInterface(): HttpInterfaceProfile;

    protected function declareLogLevel(): HttpLogLevel
    {
        return HttpLogLevel::None;
    }

    protected function declareConcurrencyPolicy(): ?ConcurrencyPolicy
    {
        return null;
    }

    /**
     * @return CorsBinding
     * @api
     */
    protected function declareCorsPolicy(): CorsBinding
    {
        return new CorsBinding(CorsPolicy::ALLOW_ALL, CorsHeaders::getDefaultCors());
    }

    /**
     * @return void
     * @throws ConcurrentHttpRequestException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    final protected function beforeEntrypointCallback(): void
    {
        if ($this->request->method === HttpMethod::OPTIONS) {
            return;
        }

        // AuthContext
        $this->authContext = $this instanceof AuthAwareRouteInterface ?
            $this->resolveAuthContext() : null;

        // Handle Request Concurrency
        if ($this->concurrencyPolicy) {
            $this->handleRequestConcurrency();
        }

        // InterfaceLog
        $routeLogLevel = $this->declareLogLevel();
        $configLogLevel = $this->interface ?
            $this->interface->config->logData : HttpLogLevel::None;

        $this->requestLogLevel = HttpLogLevel::from(max($routeLogLevel->value, $configLogLevel->value));
        if ($this->requestLogLevel->value === 0) {
            $this->requestLog = null;
            $this->requestLogSnapshot = null;
        } else {
            if (!$this->interface) {
                throw new \RuntimeException('Cannot initialize InterfaceLog without HTTP Interface declaration');
            }

            if (!isset($this->app->http->interfaceLog)) {
                throw new \RuntimeException("HTTP module does not have InterfaceLog component built");
            }

            $this->requestLogSnapshot = new RequestSnapshot(
                $this->requestLogLevel,
                $this->request,
                static::LOG_EXCLUDE_REQUEST_HEADERS,
                static::LOG_EXCLUDE_REQUEST_PARAMS,
            );

            $this->requestLog = $this->app->http->interfaceLog->createLog(
                $this,
                $this->requestLogSnapshot,
                $this->authContext ?: null,
            );
        }

        // Callback
        $this->appEndpointCallback();
    }

    /**
     * @return void
     * @throws ConcurrentHttpRequestException
     */
    private function handleRequestConcurrency(): void
    {
        $concurrencyScopeLockId = $this->concurrencyPolicy->getScopeLockId($this->userIpAddress,
            $this->authContext?->getPrimaryId());
        if (!$concurrencyScopeLockId) {
            return;
        }

        $this->concurrencyLock = (new ConcurrencyEnforcer(
            $this->concurrencyPolicy,
            SemaphoreScopes::Http,
            $this->interface->enum->value . "_" . $concurrencyScopeLockId
        ))->acquireFileLock($this->app->security->semaphore, true);
    }


    /**
     * @return never
     * @throws ApiValidationException
     */
    protected function handleInterfaceIsDisabled(): never
    {
        throw new ApiValidationException(GatewayError::INTERFACE_DISABLED);
    }

    /**
     * @param ResponseResolvedInterface|null $response
     * @return void
     */
    protected function responseDispatcherHook(?ResponseResolvedInterface $response): void
    {
        if (isset($this->concurrencyLock)) {
            try {
                $this->concurrencyLock->releaseLock();
            } catch (\Throwable $t) {
                Diagnostics::app()->error("Failed to release concurrency lock", exception: $t);
            }
        }

        if (isset($this->authContext)) {
            try {
                $this->authContext->onSendResponseCallback();
            } catch (\Throwable $t) {
                Diagnostics::app()->error("AuthContext callback triggered an exception", exception: $t);
            }
        }

        if (isset($this->requestLog)) {
            try {
                $this->requestLogSnapshot?->finalize(
                    $this->app,
                    $this->requestLogLevel,
                    $this->getResponseObject(),
                    $response,
                    static::LOG_EXCLUDE_RESPONSE_HEADERS,
                    static::LOG_EXCLUDE_RESPONSE_PARAMS
                );

                $this->app->http->interfaceLog->updateLog($this, $this->requestLog, $this->requestLogSnapshot);
            } catch (\Throwable $t) {
                $this->writeLogDumpToFile($t);
            }
        }
    }

    /**
     * @param ResponseCache $cacheableResponse
     * @param AbstractResponse $response
     * @param bool $includeAppCachedResponseHeader
     * @return never
     * @throws ResponseFromCacheException
     */
    protected function sendResponseFromCache(
        ResponseCache    $cacheableResponse,
        AbstractResponse $response,
        bool             $includeAppCachedResponseHeader = true
    ): never
    {
        $this->swapResponseObject($response);
        if ($cacheableResponse->context->cacheControlHeader) {
            $this->setCacheControl($cacheableResponse->context->cacheControlHeader);
            if ($cacheableResponse->context->cacheControlHeader->store === CacheStoreDirective::PUBLIC ||
                $cacheableResponse->context->cacheControlHeader->store === CacheStoreDirective::PRIVATE) {
                $response->headers->set("Last-Modified", gmdate("D, d M Y H:i:s", $response->createdOn) . " GMT");
            }
        }

        if ($includeAppCachedResponseHeader && $this->interface) {
            if ($this->interface->config->cachedResponseHeader) {
                $response->headers->set($this->interface->config->cachedResponseHeader,
                    strval((time() - $response->createdOn)));
            }
        }

        throw new ResponseFromCacheException();
    }

    /**
     * @param \Throwable $t
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    private function writeLogDumpToFile(\Throwable $t): void
    {
        $logFileDump = [
            "exception" => Errors::Exception2Array($t),
            "errors" => $this->app->errors->getAll(),
            "lifecycle" => $this->app->lifecycle->toArray(),
        ];

        $this->app->directories->log->getDirectory("queries", true)
            ->writeToFile(dechex($this->requestLog->id), var_export($logFileDump, true));
    }

    /**
     * @param \Throwable $t
     * @return array
     */
    protected function exceptionToArray(\Throwable $t): array
    {
        $errorObject = [
            "message" => StringHelper::getTrimmedOrNull($t->getMessage()),
            "code" => $t->getCode()
        ];

        if ($t instanceof ApiValidationException) {
            if ($t->param) {
                $errorObject["param"] = $t->param;
            }
        }

        if (!$t instanceof ApiValidationException) {
            $errorObject["exception"] = $this->exceptionFullClassname ?
                $t::class : OOP::baseClassName($t::class);
        }

        if (!$errorObject["message"]) {
            $errorObject["message"] = $this->exceptionFullClassname ?
                $t::class : OOP::baseClassName($t::class);
        }

        if ($this->exceptionReturnTrace) {
            $errorObject["file"] = $t->getFile();
            $errorObject["line"] = $t->getLine();
            $errorObject["trace"] = explode("\n", $t->getTraceAsString());
        }

        if (!$t instanceof ApiValidationException && $this->exceptionIncludePrevious) {
            $errorObject["previous"] = $t->getPrevious() ?
                $this->exceptionToArray($t->getPrevious()) : [];
        }

        return $errorObject;
    }
}