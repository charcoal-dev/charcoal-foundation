<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Http\Middleware\Logger;

use Charcoal\Http\Server\Contracts\Middleware\RequestLoggerPipeline;
use Charcoal\Http\Server\Request\Logger\RequestLoggerConstructor;

/**
 * Handles HTTP ingress logging functionality by implementing the RequestLoggerPipeline interface.
 * This class allows execution of a request logging policy to handle incoming HTTP requests.
 */
final readonly class HttpIngressLogger implements RequestLoggerPipeline
{
    public function __construct(private ?RequestLoggerConstructor $loggerPolicy = null)
    {
    }

    /**
     * @return RequestLoggerConstructor|null
     */
    public function __invoke(): ?RequestLoggerConstructor
    {
        return $this->loggerPolicy;
    }

    /**
     * @param array $params
     * @return RequestLoggerConstructor|null
     */
    public function execute(array $params): ?RequestLoggerConstructor
    {
        return $this->loggerPolicy;
    }
}