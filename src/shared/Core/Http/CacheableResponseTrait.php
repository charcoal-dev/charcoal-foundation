<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Http\Router\Controllers\CacheControl;
use Charcoal\Http\Router\Controllers\CacheStoreDirective;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;

/**
 * Trait CacheableResponseTrait
 * @package App\Shared\Core\Http
 * @mixin AppAwareEndpoint
 */
trait CacheableResponseTrait
{
    /**
     * @param CacheableResponse $cacheableResponse
     * @param AbstractControllerResponse $response
     * @param bool $includeAppCachedResponseHeader
     * @return never
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    public function sendResponseFromCache(
        CacheableResponse          $cacheableResponse,
        AbstractControllerResponse $response,
        bool                       $includeAppCachedResponseHeader = true
    ): never
    {
        $this->swapResponseObject($response);
        if ($cacheableResponse->cacheControl) {
            $this->useCacheControl($cacheableResponse->cacheControl);
            if ($cacheableResponse->cacheControl->store === CacheStoreDirective::PUBLIC ||
                $cacheableResponse->cacheControl->store === CacheStoreDirective::PRIVATE) {
                $response->headers->set("Last-Modified", gmdate("D, d M Y H:i:s", $response->createdOn) . " GMT");
            }
        }

        if ($includeAppCachedResponseHeader && $this->interface) {
            if ($this->interface->config->cachedResponseHeader) {
                $response->headers->set($this->interface->config->cachedResponseHeader,
                    strval((time() - $response->createdOn)));
            }
        }

        $this->sendResponse();
    }

    /**
     * @param string $uniqueRequestId
     * @param CacheControl|null $cacheControl
     * @return CacheableResponse
     */
    public function getCacheableResponse(string $uniqueRequestId, ?CacheControl $cacheControl): CacheableResponse
    {
        return new CacheableResponse($this, $uniqueRequestId, $cacheControl);
    }
}