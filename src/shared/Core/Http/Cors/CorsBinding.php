<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Cors;

use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Exception\CorsOriginMismatchException;

/**
 * Class CorsBinding
 * @package App\Shared\Core\Http\Cors
 */
readonly class CorsBinding
{
    public function __construct(
        public CorsPolicy  $policy = CorsPolicy::DISABLED,
        public CorsHeaders $headers,
        public array       $allowedOrigins = [],
        public bool        $terminate = true,
    )
    {
    }

    /**
     * @param AppAwareEndpoint $route
     * @return void
     * @throws CorsOriginMismatchException
     */
    public function validateOrigin(AppAwareEndpoint $route): void
    {
        if ($this->policy === CorsPolicy::DISABLED) {
            return;
        }

        if ($this->policy === CorsPolicy::ALLOW_ALL) {
            $this->headers->dispatch("*", $route);
            return;
        }

        if (in_array($route->userClient->origin, $this->allowedOrigins, true)) {
            $this->headers->dispatch($route->userClient->origin, $route);
            return;
        }

        if ($this->terminate) {
            throw new CorsOriginMismatchException();
        }
    }
}