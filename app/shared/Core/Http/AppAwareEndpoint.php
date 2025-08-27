<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Context\Api\Errors\GatewayError;
use App\Shared\Core\Http\Cache\ResponseCache;
use App\Shared\Core\Http\Exception\Cache\ResponseFromCacheException;
use App\Shared\Core\Http\Policy\Auth\AuthAwareRouteInterface;
use App\Shared\Core\Http\Policy\Auth\AuthContextInterface;
use App\Shared\Core\Http\Policy\Concurrency\ConcurrencyEnforcer;
use App\Shared\Core\Http\Policy\Concurrency\ConcurrencyPolicy;
use App\Shared\Core\Http\Policy\Cors\CorsBinding;
use App\Shared\Core\Http\Policy\Cors\CorsHeaders;
use App\Shared\Core\Http\Policy\Cors\CorsPolicy;
use App\Shared\Core\Http\Policy\DeviceFingerprintRequiredRoute;
use App\Shared\Exception\ApiValidationException;
use App\Shared\Exception\ConcurrentHttpRequestException;
use App\Shared\Exception\CorsOriginMismatchException;
use App\Shared\Foundation\Http\HttpInterface;
use App\Shared\Foundation\Http\HttpLogLevel;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogEntity;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogSnapshot;
use App\Shared\Utility\NetworkValidator;
use App\Shared\Utility\StringHelper;
use Charcoal\App\Kernel\Errors;
use Charcoal\App\Kernel\Interfaces\Http\AbstractRouteController;
use Charcoal\Buffers\AbstractFixedLenBuffer;
use Charcoal\Http\Commons\HttpMethod;
use Charcoal\Http\Router\Controllers\CacheStoreDirective;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;
use Charcoal\OOP\OOP;
use Charcoal\Semaphore\Filesystem\FileLock;

/**
 * Class AppAwareEndpoint
 * @package App\Shared\Core\Http
 * @property CharcoalApp $app
 */
abstract class AppAwareEndpoint extends AbstractRouteController
{
    protected const array LOG_IGNORE_REQUEST_HEADERS = [];
    protected const array LOG_IGNORE_RESPONSE_HEADERS = [];
    protected const array LOG_IGNORE_REQUEST_PARAMS = [];
    protected const array LOG_IGNORE_RESPONSE_PARAMS = [];

    public readonly string $userIpAddress;
    public readonly ?HttpInterfaceProfile $interface;
    public readonly ?AbstractFixedLenBuffer $deviceFp;
    protected readonly ?AuthContextInterface $authContext;

    public readonly HttpLogLevel $requestLogLevel;
    protected readonly ?InterfaceLogEntity $requestLog;
    protected readonly ?InterfaceLogSnapshot $requestLogSnapshot;

    protected readonly CorsBinding $corsBinding;
    protected readonly ?ConcurrencyPolicy $concurrencyPolicy;
    private ?FileLock $concurrencyLock = null;

    protected bool $exceptionReturnTrace = false;
    protected bool $exceptionFullClassname = false;
    protected bool $exceptionIncludePrevious = false;

    /**
     * @return void
     */
    final protected function dispatchEntrypoint(): void
    {
        // User IP Address
        $this->userIpAddress = $this->userClient->cfConnectingIP ??
            $this->userClient->xForwardedFor ??
            $this->userClient->ipAddress;

        if (!NetworkValidator::isValidIpAddress($this->userIpAddress, true, true)) {
            throw new \UnexpectedValueException("Invalid remote IP address");
        }

        // Interface Configuration
        $this->interface = $this->declareHttpInterface();
        $this->deviceFp = $this instanceof DeviceFingerprintRequiredRoute ? $this->resolveDeviceFp() : null;
        $this->corsBinding = $this->declareCorsBinding();
        $this->concurrencyPolicy = $this->declareconcurrencyPolicy();

        // Proceed to entrypoint
        parent::dispatchEntrypoint();
    }

    /**
     * @return callable
     * @throws ApiValidationException
     * @throws CorsOriginMismatchException
     */
    final protected function resolveEntrypoint(): callable
    {
        // CORS Binding
        $this->corsBinding->validateOrigin($this);

        // Interface Status
        if ($this->interface && $this->interface->config->status) {
            return $this->resolveEntryPointMethod();
        }

        // Terminate
        $this->handleInterfaceIsDisabled();
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
     * @return callable
     */
    abstract protected function resolveEntryPointMethod(): callable;

    /**
     * @return ConcurrencyPolicy|null
     */
    protected function declareConcurrencyPolicy(): ?ConcurrencyPolicy
    {
        return null;
    }

    /**
     * @return CorsBinding
     */
    protected function declareCorsBinding(): CorsBinding
    {
        return new CorsBinding(CorsPolicy::ALLOW_ALL, CorsHeaders::getDefaultCors());
    }

    /**
     * @return AbstractControllerResponse
     */
    public function response(): AbstractControllerResponse
    {
        return $this->getResponseObject();
    }

    /**
     * @return void
     */
    abstract protected function appAwareCallback(): void;

    /**
     * @return HttpInterface|null
     */
    abstract protected function declareHttpInterface(): ?HttpInterfaceProfile;

    /**
     * @return HttpLogLevel
     */
    protected function declareLogLevel(): HttpLogLevel
    {
        return HttpLogLevel::NONE;
    }

    /**
     * @return void
     * @throws ConcurrentHttpRequestException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
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
            $this->interface->config->logData : HttpLogLevel::NONE;

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

            $this->requestLogSnapshot = new InterfaceLogSnapshot(
                $this->requestLogLevel,
                $this->request,
                static::LOG_IGNORE_REQUEST_HEADERS,
                static::LOG_IGNORE_REQUEST_PARAMS,
            );

            $this->requestLog = $this->app->http->interfaceLog->createLog(
                $this,
                $this->requestLogSnapshot,
                $this->authContext ?: null,
            );
        }

        // Callback
        $this->appAwareCallback();
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

        $this->concurrencyLock = (new ConcurrencyEnforcer($this->concurrencyPolicy, $concurrencyScopeLockId))
            ->acquireFileLock($this->interface, $this->app->directories->semaphore, true);
    }

    /**
     * @return void
     */
    abstract protected function prepareResponseCallback(): void;

    /**
     * @return never
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    final public function sendResponse(): never
    {
        if (isset($this->concurrencyLock)) {
            try {
                $this->concurrencyLock->releaseLock();
            } catch (\Throwable $t) {
                $this->app->lifecycle->exception($t);
            }
        }

        if (isset($this->authContext)) {
            try {
                $this->authContext->onSendResponseCallback();
            } catch (\Throwable $t) {
                $this->app->lifecycle->exception($t);
            }
        }

        if (isset($this->requestLog)) {
            try {
                $this->requestLogSnapshot?->finalise(
                    $this->app,
                    $this->requestLogLevel,
                    $this->getResponseObject(),
                    static::LOG_IGNORE_RESPONSE_HEADERS,
                    static::LOG_IGNORE_RESPONSE_PARAMS
                );

                $this->app->http->interfaceLog->updateLog($this, $this->requestLog, $this->requestLogSnapshot);
            } catch (\Throwable $t) {
                $this->writeLogDumpToFile($t);
            }
        }

        $this->prepareResponseCallback();
        parent::sendResponse();
    }

    /**
     * @param ResponseCache $cacheableResponse
     * @param AbstractControllerResponse $response
     * @param bool $includeAppCachedResponseHeader
     * @return never
     * @throws ResponseFromCacheException
     */
    protected function sendResponseFromCache(
        ResponseCache              $cacheableResponse,
        AbstractControllerResponse $response,
        bool                       $includeAppCachedResponseHeader = true
    ): never
    {
        $this->swapResponseObject($response);
        if ($cacheableResponse->context->cacheControlHeader) {
            $this->useCacheControl($cacheableResponse->context->cacheControlHeader);
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