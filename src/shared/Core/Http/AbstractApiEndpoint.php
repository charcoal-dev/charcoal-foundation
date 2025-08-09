<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\Context\Api\GatewayError;
use App\Shared\Core\Http\Api\ApiErrorCodeInterface;
use App\Shared\Core\Http\Api\ApiInterfaceBinding;
use App\Shared\Core\Http\Api\ApiNamespaceInterface;
use App\Shared\Core\Http\Api\ApiResponse;
use App\Shared\Exception\ApiEntrypointException;
use App\Shared\Exception\ApiResponseFinalizedException;
use App\Shared\Exception\ApiValidationException;
use App\Shared\Exception\CacheableResponseSuccessException;
use App\Shared\Exception\ConcurrentHttpRequestException;
use App\Shared\Exception\CorsOriginMismatchException;
use App\Shared\Exception\HttpOptionsException;
use App\Shared\Exception\WrappedException;
use App\Shared\Utility\StringHelper;
use App\Shared\Validation\ValidationException;
use Charcoal\Http\Commons\HttpMethod;
use Charcoal\Http\Router\Controllers\Response\NoContentResponse;

/**
 * Class AbstractApiEndpoint
 * @package App\Shared\Core\Http
 * @property ApiInterfaceBinding $interface
 * @method never put()
 * @method never get()
 * @method never post()
 * @method never delete()
 */
abstract class AbstractApiEndpoint extends AppAwareEndpoint
{
    public readonly string $userAgent;
    protected bool $allowOptionsCall = true;

    /**
     * @return ApiNamespaceInterface
     */
    abstract protected function declareApiNamespace(): ApiNamespaceInterface;

    /**
     * @return ApiInterfaceBinding
     */
    abstract protected function declareApiInterface(): ApiInterfaceBinding;

    /**
     * @return HttpInterfaceBinding|null
     */
    final protected function declareHttpInterface(): ?HttpInterfaceBinding
    {
        return $this->declareApiInterface();
    }

    /**
     * @return never
     * @throws ApiValidationException
     * @throws HttpOptionsException
     */
    protected function handleInterfaceIsDisabled(): never
    {
        if ($this->request->method === HttpMethod::OPTIONS) {
            $this->response()->headers->set("Allow", "");
            throw new HttpOptionsException();
        }

        parent::handleInterfaceIsDisabled();
    }

    /**
     * This method is not to be used because of ApiResponseFinalizedException
     * @return void
     */
    final protected function afterEntrypointCallback(): void
    {
    }

    /**
     * @return callable
     * @throws ApiEntrypointException
     * @throws ApiValidationException
     */
    protected function resolveEntryPointMethod(): callable
    {
        $userAgent = StringHelper::getTrimmedOrNull($this->userClient->userAgent);
        if (!$userAgent) {
            throw new ApiValidationException(GatewayError::USER_AGENT_REQUIRED);
        }

        $this->userAgent = $userAgent;

        $httpMethod = strtolower($this->request->method->name);
        if ($httpMethod === "options" && !$this->allowOptionsCall) {
            throw new ApiEntrypointException();
        }

        if (method_exists($this, $httpMethod)) {
            return [$this, $httpMethod];
        }

        throw new ApiEntrypointException();
    }

    /**
     * @param \Throwable $t
     * @return void
     */
    protected function handleException(\Throwable $t): void
    {
        if ($t instanceof ApiResponseFinalizedException || $t instanceof CacheableResponseSuccessException) {
            return;
        }

        if ($t instanceof HttpOptionsException) {
            $this->swapResponseObject(new NoContentResponse(204, $this->response()));
            return;
        }

        // Handleable Individual Exception?
        $apiError = $this->individualExceptionHandler($t);
        if ($apiError) {
            $errorObject = ["message" => $apiError->getErrorMessage($t, $this) ?? $apiError->name];
            $errorCode = $apiError->getErrorCode($t, $this);
            if (is_int($errorCode)) {
                $errorObject["code"] = $errorCode;
            }
        } else {
            $errorObject = $this->exceptionToArray($t);
        }

        // Log to Lifecycle
        if ($t instanceof ApiValidationException || $t instanceof WrappedException) {
            if ($t->getPrevious()) {
                $this->app->lifecycle->exception($t->getPrevious());
            }
        } else {
            $this->app->lifecycle->exception($t);
        }

        try {
            $this->response()->setError(count($errorObject) === 1 && isset($errorObject["message"]) ?
                $errorObject["message"] : $errorObject, $apiError?->getHttpCode());
        } catch (ApiResponseFinalizedException) {
        }
    }

    /**
     * @param \Throwable $t
     * @return ApiErrorCodeInterface|null
     */
    protected function individualExceptionHandler(\Throwable $t): ?ApiErrorCodeInterface
    {
        if ($t instanceof ConcurrentHttpRequestException) {
            return GatewayError::CONCURRENT_TERMINATE;
        }

        if ($t instanceof CorsOriginMismatchException) {
            return GatewayError::CORS_TERMINATE;
        }

        if ($t instanceof ApiValidationException) {
            if ($t->errorCode) {
                return $t->errorCode;
            }
        }

        if ($t instanceof ValidationException) {
            return $this->handleValidationException($t);
        }

        if ($t instanceof ApiEntrypointException) {
            return GatewayError::METHOD_NOT_ALLOWED;
        }

        if ($t instanceof \ErrorException) {
            if ($this->app->errors->isFatalError($t->getSeverity())) {
                return GatewayError::SERVER_ERROR;
            }

            return GatewayError::FATAL_ERROR;
        }

        return null;
    }

    /**
     * @param ValidationException $exception
     * @return ApiErrorCodeInterface|null
     * @noinspection PhpUnusedParameterInspection
     */
    protected function handleValidationException(ValidationException $exception): ?ApiErrorCodeInterface
    {
        return GatewayError::VALIDATION_ERROR;
    }

    /**
     * @return ApiResponse
     */
    protected function initEmptyResponse(): ApiResponse
    {
        return new ApiResponse();
    }

    /**
     * @return void
     */
    protected function prepareResponseCallback(): void
    {
        $response = $this->response();
        if ($response instanceof ApiResponse) {
            $response->prepareResponseCallback($this, $this->authContext ?? null, $this->requestLog ?? null);
        }
    }

    /**
     * @return ApiResponse|NoContentResponse
     */
    public function response(): ApiResponse|NoContentResponse
    {
        /** @var ApiResponse|NoContentResponse */
        return parent::response();
    }

    /**
     * @return never
     * @throws HttpOptionsException
     */
    protected function options(): never
    {
        $options = [];
        foreach (["get", "post", "put", "delete"] as $httpOpt) {
            if (method_exists($this, $httpOpt)) {
                $options[] = strtoupper($httpOpt);
            }
        }

        $this->response()->headers->set("Allow", implode(", ", $options));
        throw new HttpOptionsException();
    }
}