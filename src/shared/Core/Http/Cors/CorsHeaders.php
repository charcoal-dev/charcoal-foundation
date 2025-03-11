<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Cors;

use App\Shared\Core\Http\AppAwareEndpoint;

/**
 * Class CorsHeaders
 * @package App\Shared\Core\Http\Cors
 */
class CorsHeaders
{
    private string $methods;
    private string $allowHeaders;
    private string $exposeHeaders;
    public bool $allowCredentials = false;
    public ?int $maxAge = null;

    /**
     * @return static
     */
    public static function getDefaultCors(): static
    {
        $cors = new static();
        $cors->allowMethods("GET", "POST", "PUT", "DELETE", "OPTIONS");
        $cors->allowHeaders("Authorization", "Content-Type", "Content-Length");
        $cors->exposeHeaders("Authorization", "Content-Type", "Content-Length",
            "Content-Disposition",
            "Content-Transfer-Encoding");

        $cors->maxAge = 3600;
        //$cors->allowCredentials = true;
        return $cors;
    }

    /**
     * @param string ...$methods
     * @return $this
     */
    public function allowMethods(string ...$methods): static
    {
        $this->methods = implode(",", $methods);
        return $this;
    }

    /**
     * @param string ...$headers
     * @return $this
     */
    public function allowHeaders(string ...$headers): static
    {
        $this->allowHeaders = implode(",", $headers);
        return $this;
    }

    /**
     * @param string ...$headers
     * @return $this
     */
    public function exposeHeaders(string ...$headers): static
    {
        $this->exposeHeaders = implode(",", $headers);
        return $this;
    }

    /**
     * @param string $origin
     * @param AppAwareEndpoint $route
     * @return void
     */
    public function dispatch(string $origin, AppAwareEndpoint $route): void
    {
        $route->response()->headers->set("Access-Control-Allow-Origin", $origin)
            ->set("Access-Control-Allow-Methods", $this->methods)
            ->set("Access-Control-Allow-Headers", $this->allowHeaders)
            ->set("Access-Control-Expose-Headers", $this->exposeHeaders);

        if ($this->maxAge > 0) {
            $route->response()->headers->set("Access-Control-Max-Age", strval($this->maxAge));
        }

        if ($this->allowCredentials) {
            $route->response()->headers->set("Access-Control-Allow-Credentials", "true");
        }
    }
}