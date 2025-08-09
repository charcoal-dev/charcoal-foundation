<?php
declare(strict_types=1);

namespace App\Shared\Context\Api;

use App\Shared\Core\Http\AbstractApiEndpoint;
use App\Shared\Core\Http\Api\Error\ApiErrorCodeInterface;
use App\Shared\Exception\ApiValidationException;
use App\Shared\Validation\ValidationException;

/**
 * Class GatewayError
 * @package App\Shared\Context\Api
 */
enum GatewayError: string implements ApiErrorCodeInterface
{
    case INTERFACE_DISABLED = 'HTTP Interface "%s" is DISABLED';
    case CONCURRENT_TERMINATE = "Too many requests";
    case CORS_TERMINATE = "CORS origin not allowed";
    case METHOD_NOT_ALLOWED = "Method not allowed";
    case SERVER_ERROR = "Internal server error";
    case FATAL_ERROR = "An error occurred";
    case USER_AGENT_REQUIRED = "User-Agent header is required";
    case UNRECOGNIZED_REQUEST_PAYLOAD = 'Unrecognized parameter: "%s"';
    case VALIDATION_ERROR = "Validation error: %s";

    /**
     * @param \Throwable|null $context
     * @param AbstractApiEndpoint|null $route
     * @return string
     */
    public function getErrorMessage(\Throwable $context = null, AbstractApiEndpoint $route = null): string
    {
        if ($this === self::VALIDATION_ERROR && $context instanceof ValidationException) {
            return sprintf($this->value, $context->getMessage());
        }

        if ($this === self::INTERFACE_DISABLED) {
            return sprintf($this->value, $route->interface->enum->name);
        }

        if ($this === self::UNRECOGNIZED_REQUEST_PAYLOAD) {
            $param = $context instanceof ApiValidationException ? ($context->baggage[0] ?? null) : null;
            return sprintf($this->value, $param ?? "");
        }

        return $this->value;
    }

    /**
     * @param \Throwable|null $context
     * @param AbstractApiEndpoint|null $route
     * @return int|string|null
     */
    public function getErrorCode(\Throwable $context = null, AbstractApiEndpoint $route = null): null|int|string
    {
        return $context->getCode() ?: null;
    }

    /**
     * @return int
     */
    public function getHttpCode(): int
    {
        return match ($this) {
            self::CONCURRENT_TERMINATE => 429,
            self::CORS_TERMINATE => 403,
            self::METHOD_NOT_ALLOWED => 405,
            self::SERVER_ERROR => 500,
            default => 400
        };
    }
}