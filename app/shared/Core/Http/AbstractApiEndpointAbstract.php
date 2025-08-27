<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\Context\Api\Errors\GatewayError;
use App\Shared\Core\Http\Api\ApiInterfaceProfile;
use App\Shared\Core\Http\Api\ApiNamespaceInterface;
use App\Shared\Core\Http\Api\ApiResponse;
use App\Shared\Core\Http\Api\Error\ApiTranslatedErrorInterface;
use App\Shared\Core\Http\Api\Error\ValidationErrorTranslator;
use App\Shared\Core\Http\Exceptions\Api\EntrypointException;
use App\Shared\Core\Http\Exceptions\Api\ResponseFinalizedException;
use App\Shared\Core\Http\Exceptions\Cache\ResponseFromCacheException;
use App\Shared\Exceptions\ApiValidationException;
use App\Shared\Exceptions\Http\ConcurrentHttpRequestException;
use App\Shared\Exceptions\Http\CorsOriginMismatchException;
use App\Shared\Exceptions\Http\HttpOptionsException;
use App\Shared\Utility\StringHelper;
use App\Shared\Validation\ValidationException;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\App\Kernel\Support\DtoHelper;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Http\Commons\Enums\HttpMethod;

/**
 * Class AbstractApiEndpoint
 * @package App\Shared\Core\Http
 * @property ApiInterfaceProfile $interface
 * @method never put()
 * @method never get()
 * @method never post()
 * @method never delete()
 */
abstract class AbstractApiEndpointAbstract extends AbstractAppEndpoint
{
    public readonly string $userAgent;
    protected bool $allowOptionsCall = true;

    /**
     * @return ApiNamespaceInterface
     * @api
     */
    abstract protected function declareApiNamespace(): ApiNamespaceInterface;

    /**
     * @return ApiInterfaceProfile
     */
    abstract protected function declareApiInterface(): ApiInterfaceProfile;

    /**
     * @return ApiInterfaceProfile
     */
    final protected function declareHttpInterface(): ApiInterfaceProfile
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
     * @throws EntrypointException
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
            throw new EntrypointException();
        }

        if (method_exists($this, $httpMethod)) {
            return [$this, $httpMethod];
        }

        throw new EntrypointException();
    }

    /**
     * @param \Throwable $t
     * @return void
     * @throws \Charcoal\Http\Router\Exceptions\ResponseDispatchedException
     */
    protected function handleException(\Throwable $t): void
    {
        if ($t instanceof ResponseFinalizedException || $t instanceof ResponseFromCacheException) {
            return;
        }

        if ($t instanceof HttpOptionsException) {
            $this->terminate(204);
        }

        // Handleable Individual Exception?
        $apiError = $this->individualExceptionHandler($t);
        if ($apiError) {
            $errorObject = ["message" => $apiError->getErrorMessage($t, $this) ?? $apiError->name];
            $errorCode = $apiError->getErrorCode($t, $this);
            if ($errorCode && $errorCode !== 0) {
                $errorObject["code"] = $errorCode;
            }
        } else {
            $errorObject = DtoHelper::getExceptionObject($t);
        }

        // Log to Lifecycle
        if ($t instanceof ApiValidationException || $t instanceof WrappedException) {
            if ($t->getPrevious()) {
                Diagnostics::app()->error("Exception caught during validation", exception: $t->getPrevious());
            }
        } else {
            Diagnostics::app()->error("Exception caught during validation", exception: $t->getPrevious());
        }

        try {
            $this->response()->setError(count($errorObject) === 1 && isset($errorObject["message"]) ?
                $errorObject["message"] : $errorObject, $apiError?->getHttpCode());
        } catch (ResponseFinalizedException) {
        }
    }

    /**
     * @param \Throwable $t
     * @return ApiTranslatedErrorInterface|null
     */
    protected function individualExceptionHandler(\Throwable $t): ?ApiTranslatedErrorInterface
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

        if ($t instanceof EntrypointException) {
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
     * @return ApiTranslatedErrorInterface|null
     */
    protected function handleValidationException(ValidationException $exception): ?ApiTranslatedErrorInterface
    {
        return ValidationErrorTranslator::getTranslated($exception->errorCode);
    }

    /**
     * @return ApiResponse
     */
    public function response(): ApiResponse
    {
        /** @var ApiResponse */
        return $this->getResponseObject();
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