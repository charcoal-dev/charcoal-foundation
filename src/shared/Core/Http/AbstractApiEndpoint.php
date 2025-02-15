<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;
use Charcoal\Http\Router\Controllers\Response\PayloadResponse;

abstract class AbstractApiEndpoint extends AppAwareEndpoint
{
    protected function resolveEntrypoint(): callable
    {
        $httpMethod = strtolower($this->request->method->name);
        if (method_exists($this, $httpMethod)) {
            return [$this, $httpMethod];
        }

        if ($httpMethod === "options") {

        }

    }

    protected function handleException(\Throwable $t): void
    {
        // TODO: Implement handleException() method.
    }

    /**
     * @return array
     */
    protected function getHttpOptions(): array
    {
        $options = [];
        foreach (["get", "post", "put", "delete"] as $httpOpt) {
            if (method_exists($this, $httpOpt)) {
                $options[] = strtoupper($httpOpt);
            }
        }

        return $options;
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

    protected function appAwareCallback(): void
    {
    }

    protected function declareHttpInterface(): ?HttpInterfaceBinding
    {
        // TODO: Implement declareHttpInterface() method.
    }
}