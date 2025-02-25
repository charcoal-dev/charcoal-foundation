<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\Auth\AuthContextResolverInterface;
use App\Shared\Core\Http\Auth\AuthRouteInterface;
use App\Shared\Core\Http\Response\CacheableResponse;
use App\Shared\Exception\ApiValidationException;
use App\Shared\Exception\ConcurrentHttpRequestException;
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
use Charcoal\Semaphore\Exception\SemaphoreException;
use Charcoal\Semaphore\Filesystem\FileLock;
use Charcoal\Semaphore\FilesystemSemaphore;

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
    public readonly ?HttpInterfaceBinding $interface;
    public readonly ?AbstractFixedLenBuffer $deviceFp;
    protected readonly ?AuthContextResolverInterface $authContext;

    public readonly HttpLogLevel $requestLogLevel;
    private readonly ?InterfaceLogEntity $requestLog;
    private readonly ?InterfaceLogSnapshot $requestLogSnapshot;
    protected readonly ?ConcurrencyBinding $concurrencyBinding;
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
        $this->authContext = $this instanceof AuthRouteInterface ? $this->resolveAuthContext() : null;
        $this->concurrencyBinding = $this->declareConcurrencyBinding();

        // Proceed to entrypoint
        parent::dispatchEntrypoint();
    }

    /**
     * @return ConcurrencyBinding|null
     */
    protected function declareConcurrencyBinding(): ?ConcurrencyBinding
    {
        return null;
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
    abstract protected function declareHttpInterface(): ?HttpInterfaceBinding;

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
        // Interface Status
        if ($this->interface && !$this->interface->config->status) {
            throw new \RuntimeException(
                sprintf('HTTP Interface "%s" is DISABLED', $this->interface->enum->name)
            );
        }

        // Handle Request Concurrency
        if ($this->concurrencyBinding) {
            $this->handleRequestConcurrency();
        }

        // InterfaceLog
        $routeLogLevel = $this->declareLogLevel();
        $configLogLevel = $this->interface ? $this->interface->config->logData : HttpLogLevel::NONE;
        if ($this->request->method === HttpMethod::OPTIONS) {
            if (!$this->interface?->config?->logHttpMethodOptions) {
                $configLogLevel = HttpLogLevel::NONE;
                $routeLogLevel = HttpLogLevel::NONE;
            }
        }

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

            $this->requestLogSnapshot = $this->requestLogLevel->value >= 2 ? new InterfaceLogSnapshot(
                $this->requestLogLevel,
                $this->request,
                static::LOG_IGNORE_REQUEST_HEADERS,
                static::LOG_IGNORE_REQUEST_PARAMS,
            ) : null;

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
        $concurrencyLockKey = match ($this->concurrencyBinding->policy) {
            ConcurrencyPolicy::IP_ADDR => "ip_" . md5($this->userIpAddress),
            ConcurrencyPolicy::AUTH_CONTEXT => "auth_" . $this->authContext->getPrimaryId(),
            default => throw new \LogicException("Cannot determine semaphore key for concurrency policy")
        };

        try {
            $httpSemaphore = new FilesystemSemaphore(
                $this->app->directories->semaphore->getDirectory($this->interface?->enum->value ?? "http", true)
            );
        } catch (\Exception $e) {
            throw new \LogicException("Failed to initialize HTTP Semaphore", previous: $e);
        }

        try {
            $this->concurrencyLock = $httpSemaphore->obtainLock($concurrencyLockKey,
                $this->concurrencyBinding->maximumWaitTime > 0 ? $this->concurrencyBinding->tickInterval : null,
                max($this->concurrencyBinding->maximumWaitTime, 0),
            );

            $this->concurrencyLock->setAutoRelease();
        } catch (SemaphoreException) {
            throw new ConcurrentHttpRequestException($this->concurrencyBinding->policy, $concurrencyLockKey);
        }
    }

    /**
     * @return never
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    public function sendResponse(): never
    {
        if (isset($this->concurrencyLock)) {
            try {
                $this->concurrencyLock->releaseLock();
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

        parent::sendResponse();
    }

    /**
     * @param CacheableResponse $cacheableResponse
     * @param AbstractControllerResponse $response
     * @param bool $includeAppCachedResponseHeader
     * @return never
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    protected function sendResponseFromCache(
        CacheableResponse          $cacheableResponse,
        AbstractControllerResponse $response,
        bool                       $includeAppCachedResponseHeader = true
    ): never
    {
        $this->swapResponseObject($response);
        if ($cacheableResponse->cacheControl) {
            $this->useCacheControl($cacheableResponse->cacheControl);
            if ($cacheableResponse->cacheControl->store === CacheStoreDirective::PUBLIC ||
                $cacheableResponse->cacheControl->store === CacheStoreDirective::PRIVATE) {
                $response->headers->set("Last-Modified", gmdate("D, d M Y H:i:s", $response->createdOn) . " GMT");
            }
        }

        if ($includeAppCachedResponseHeader && $this->interface) {
            if ($this->interface->config->cachedResponseHeader) {
                $response->headers->set($this->interface->config->cachedResponseHeader,
                    strval((time() - $response->createdOn)));
            }
        }

        $this->sendResponse();
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
            $errorObject["previous"] = $this->exceptionToArray($t->getPrevious());
        }

        return $errorObject;
    }
}