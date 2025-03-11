<?php
declare(strict_types=1);

namespace App\Shared\Context;

use App\Shared\Core\Http\AbstractApiEndpoint;
use App\Shared\Core\Http\Api\ApiErrorCodeInterface;

/**
 * Class ApiError
 * @package App\Shared\Context
 */
enum ApiError: string implements ApiErrorCodeInterface
{
    case CONCURRENT_TERMINATE = "Too many requests";
    case CORS_TERMINATE = "CORS origin not allowed";
    case METHOD_NOT_ALLOWED = "Method not allowed";
    case SERVER_ERROR = "Internal server error";
    case FATAL_ERROR = "An error occurred";

    public function getErrorMessage(\Throwable $context = null, AbstractApiEndpoint $route = null): string
    {
        return $this->value;
    }

    public function getErrorCode(\Throwable $context = null, AbstractApiEndpoint $route = null): null|int|string
    {
        return $context->getCode() ?: null;
    }

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