<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\Context\ApiError;
use App\Shared\Core\Http\Api\ApiErrorCodeInterface;
use App\Shared\Core\Http\Api\ApiInterfaceBinding;
use App\Shared\Core\Http\Api\ApiNamespaceInterface;
use App\Shared\Core\Http\Api\ApiResponse;
use App\Shared\Exception\ApiEntrypointException;
use App\Shared\Exception\ApiValidationException;
use App\Shared\Exception\ConcurrentHttpRequestException;
use App\Shared\Exception\CorsOriginMismatchException;
use App\Shared\Exception\HttpOptionsException;
use App\Shared\Exception\WrappedException;
use Charcoal\Http\Router\Controllers\Response\NoContentResponse;

/**
 * Class AbstractApiEndpoint
 * @package App\Shared\Core\Http
 * @property ApiInterfaceBinding $interface
 */
abstract class AbstractApiEndpoint extends AppAwareEndpoint
{
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
     * @return callable
     * @throws ApiEntrypointException
     */
    protected function resolveEntryPointMethod(): callable
    {
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

            $this->responseFromErrorObject($apiError->getHttpCode(), count($errorObject) === 1 ?
                $errorObject["message"] : $errorObject);
            return;
        }

        // Log to Lifecycle
        if ($t instanceof ApiValidationException || $t instanceof WrappedException) {
            if ($t->getPrevious()) {
                $this->app->lifecycle->exception($t->getPrevious());
            }
        } else {
            $this->app->lifecycle->exception($t);
        }

        $this->responseFromErrorObject(null, $this->exceptionToArray($t));
    }

    /**
     * @param int|null $statusCode
     * @param string|array $errorObject
     * @return void
     */
    private function responseFromErrorObject(null|int $statusCode, string|array $errorObject): void
    {
        if (!$statusCode) {
            $statusCode = 400;
        }

        $this->response()->setSuccess(false, $statusCode)
            ->set("error", $errorObject);
    }

    /**
     * @param \Throwable $t
     * @return ApiErrorCodeInterface|null
     */
    protected function individualExceptionHandler(\Throwable $t): ?ApiErrorCodeInterface
    {
        if ($t instanceof ConcurrentHttpRequestException) {
            return ApiError::CONCURRENT_TERMINATE;
        }

        if ($t instanceof CorsOriginMismatchException) {
            return ApiError::CORS_TERMINATE;
        }

        if ($t instanceof ApiValidationException) {
            if ($t->errorCode) {
                return $t->errorCode;
            }
        }

        if ($t instanceof ApiEntrypointException) {
            return ApiError::METHOD_NOT_ALLOWED;
        }

        if ($t instanceof \ErrorException) {
            if ($this->app->errors->isFatalError($t->getSeverity())) {
                return ApiError::SERVER_ERROR;
            }

            return ApiError::FATAL_ERROR;
        }

        return null;
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
            $response->prepareResponseCallback($this, $this->authContext, $this->requestLog ?? null);
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