<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;

/**
 * Interface CacheableResponseInterface
 * @package App\Shared\Core\Http
 */
interface CacheableResponseInterface
{
    function hasCachedResponse(string $cacheableRequestId): bool;

    function getCachedResponse(string $cacheableRequestId): ?AbstractControllerResponse;

    function storeCachedResponse(string $cacheableRequestId, AbstractControllerResponse $response): void;
}