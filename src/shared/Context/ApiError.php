<?php
declare(strict_types=1);

namespace App\Shared\Context;

use App\Shared\Core\Http\AbstractApiEndpoint;
use App\Shared\Core\Http\Api\ApiErrorCodeInterface;
use App\Shared\Exception\HttpUnrecognizedPayloadException;

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
    case USER_AGENT_REQUIRED = "User-Agent header is required";
    case UNRECOGNIZED_REQUEST_PAYLOAD = 'Unrecognized parameter: "%s"';

    public function getErrorMessage(\Throwable $context = null, AbstractApiEndpoint $route = null): string
    {
        if ($context instanceof HttpUnrecognizedPayloadException) {
            return sprintf($this->value, $context->unrecognized[0]);
        }

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