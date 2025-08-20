<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api\Error;

use App\Shared\Core\Http\AbstractApiEndpointAbstract;

/**
 * Interface ApiTranslatedErrorInterface
 * @package App\Shared\Core\Http\Api\Error
 */
interface ApiTranslatedErrorInterface extends \BackedEnum
{
    /**
     * @param \Throwable|null $context
     * @param AbstractApiEndpointAbstract|null $route
     * @return string|null
     */
    public function getErrorMessage(\Throwable $context = null, AbstractApiEndpointAbstract $route = null): ?string;

    /**
     * @param \Throwable|null $context
     * @param AbstractApiEndpointAbstract|null $route
     * @return int|string|null
     */
    public function getErrorCode(\Throwable $context = null, AbstractApiEndpointAbstract $route = null): null|int|string;

    /**
     * @return int|null
     */
    public function getHttpCode(): ?int;
}