<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http\Api\Error;

use App\Shared\Core\Http\AbstractApiEndpointAbstract;

/**
 * Trait ApiErrorCodeDefaults
 * @package App\Shared\Core\Http\Api\Error
 * @mixin ApiTranslatedErrorInterface
 */
trait ApiTranslatedErrorDefaults
{
    public function getErrorMessage(\Throwable $context = null, AbstractApiEndpointAbstract $route = null): ?string
    {
        return $this->value;
    }

    public function getErrorCode(\Throwable $context = null, AbstractApiEndpointAbstract $route = null): null|int|string
    {
        return null;
    }

    public function getHttpCode(): ?int
    {
        return null;
    }
}