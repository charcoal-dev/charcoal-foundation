<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\Core\Http\AbstractApiEndpoint;

/**
 * Trait ApiErrorCodeDefaults
 * @package App\Shared\Context\Api\Errors
 * @mixin ApiErrorCodeInterface
 */
trait ApiErrorCodeDefaults
{
    public function getErrorMessage(\Throwable $context = null, AbstractApiEndpoint $route = null): ?string
    {
        return $this->value;
    }

    public function getErrorCode(\Throwable $context = null, AbstractApiEndpoint $route = null): null|int|string
    {
        return null;
    }

    public function getHttpCode(): ?int
    {
        return null;
    }
}