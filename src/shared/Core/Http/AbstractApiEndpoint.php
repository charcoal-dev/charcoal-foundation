<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\Exception\ApiEntrypointException;
use App\Shared\Exception\ApiValidationException;
use Charcoal\Http\Router\Controllers\Response\PayloadResponse;

/**
 * Class AbstractApiEndpoint
 * @package App\Shared\Core\Http
 */
abstract class AbstractApiEndpoint extends AppAwareEndpoint
{
    protected bool $allowOptionsCall = true;

    /**
     * @return callable
     * @throws ApiEntrypointException
     */
    protected function resolveEntrypoint(): callable
    {
        $this->response()->setStatusCode(204);

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
        // Handleable Individual Exception?
        list($statusCode,
            $errorMessage,
            $errorCode) = $this->individualExceptionHandler($t);

        if ($errorMessage || $errorCode) {
            $this->responseErrorObject($statusCode, ["message" => $errorMessage, "code" => $errorCode]);
            return;
        }

        // Log to Lifecycle
        if ($t instanceof ApiValidationException) {
            if ($t->getPrevious()) {
                $this->app->lifecycle->exception($t->getPrevious());
            }
        } else {
            $this->app->lifecycle->exception($t);
        }

        $this->responseErrorObject($statusCode, $this->exceptionToArray($t, "exception"));
    }

    /**
     * @param int|null $statusCode
     * @param array $errorObject
     * @return void
     */
    private function responseErrorObject(null|int $statusCode, array $errorObject): void
    {
        if (!$statusCode) {
            $statusCode = 400;
        }

        $this->response()->setStatusCode($statusCode);
        $this->response()->set("status", false);
        $this->response()->set("error", $errorObject);
    }

    /**
     * @param \Throwable $t
     * @return array
     */
    protected function individualExceptionHandler(\Throwable $t): array
    {
        if ($t instanceof ApiEntrypointException) {
            return [405, "Method not allowed", null];
        }

        return [400, null, null];
    }

    /**
     * @return PayloadResponse
     */
    protected function initEmptyResponse(): PayloadResponse
    {
        return new PayloadResponse();
    }

    /**
     * @return PayloadResponse
     */
    protected function response(): PayloadResponse
    {
        /** @var PayloadResponse */
        return $this->getResponseObject();
    }

    /**
     * @return array
     */
    protected function options(): array
    {
        $options = [];
        foreach (["get", "post", "put", "delete"] as $httpOpt) {
            if (method_exists($this, $httpOpt)) {
                $options[] = strtoupper($httpOpt);
            }
        }

        return $options;
    }
}