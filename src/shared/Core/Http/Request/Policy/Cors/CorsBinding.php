<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Policy\Cors;

use App\Shared\Core\Http\AbstractAppEndpoint;
use App\Shared\Enums\Http\CorsPolicy;
use App\Shared\Exceptions\Http\CorsOriginMismatchException;
use App\Shared\Utility\NetworkHelper;

/**
 * Class CorsBinding
 * @package App\Shared\Core\Http\Cors
 */
readonly class CorsBinding
{
    /**
     * @param CorsPolicy $policy
     * @param CorsHeaders $headers
     * @param array $allowedOrigins
     * @param bool $terminate
     */
    public function __construct(
        public CorsPolicy  $policy = CorsPolicy::DISABLED,
        public CorsHeaders $headers,
        public array       $allowedOrigins = [],
        public bool        $terminate = true,
    )
    {
    }

    /**
     * @param AbstractAppEndpoint $route
     * @return void
     * @throws CorsOriginMismatchException
     */
    public function validateOrigin(AbstractAppEndpoint $route): void
    {
        if ($this->policy === CorsPolicy::DISABLED) {
            return;
        }

        if ($this->policy === CorsPolicy::ALLOW_ALL) {
            $this->headers->dispatch("*", $route);
            return;
        }

        if (NetworkHelper::isValidHttpOrigin($route->userClient->origin)) {
            if (in_array(strtolower($route->userClient->origin), $this->allowedOrigins, true)) {
                $this->headers->dispatch($route->userClient->origin, $route);
                return;
            }
        }

        if ($this->terminate) {
            throw new CorsOriginMismatchException();
        }
    }
}