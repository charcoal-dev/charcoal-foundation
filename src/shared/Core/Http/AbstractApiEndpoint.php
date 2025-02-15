<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

class AbstractApiEndpoint extends AppAwareEndpoint
{
    use CacheableResponseTrait;

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
}