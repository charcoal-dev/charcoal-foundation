<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Http\Router\Controllers\CacheControl;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;

/**
 * Interface CacheableResponseInterface
 * @package App\Shared\Core\Http
 */
interface CacheableResponseInterface
{
    /**
     * @param string $uniqueRequestId
     * @param CacheControl|null $cacheControl
     * @return CacheableResponse
     */
    public function getCacheableResponse(
        string        $uniqueRequestId,
        ?CacheControl $cacheControl
    ): CacheableResponse;

    /**
     * @param CacheableResponse $cacheableResponse
     * @param AbstractControllerResponse $response
     * @param bool $includeAppCachedResponseHeader
     * @return never
     */
    public function sendResponseFromCache(
        CacheableResponse          $cacheableResponse,
        AbstractControllerResponse $response,
        bool                       $includeAppCachedResponseHeader = true
    ): never;
}