<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\Core\Http\AbstractAppEndpoint;
use App\Shared\Core\Http\Cache\ResponseCache;
use App\Shared\Core\Http\Cache\ResponseCacheContext;
use App\Shared\Core\Http\Exception\Api\ResponseFinalizedException;
use App\Shared\Core\Http\Exception\Cache\ResponseFromCacheException;
use App\Shared\Core\Http\Exception\Cache\ResponseInvalidatedException;
use App\Shared\Enums\Http\HttpInterface;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\Http\Router\Response\AbstractResponse;

/**
 * Trait CacheableResponseTrait
 * @package App\Shared\Core\Http\Response
 * @mixin AbstractAppEndpoint
 * @api
 */
trait CacheableResponseTrait
{
    /**
     * @param ResponseCacheContext $context
     * @param HttpInterface|null $interface
     * @return ResponseCache
     */
    protected function getCacheableResponse(
        ResponseCacheContext $context,
        ?HttpInterface       $interface = null
    ): ResponseCache
    {
        return new ResponseCache($this->app, $interface ?? $this->interface->enum, $context);
    }

    /**
     * @param ResponseCache $cacheable
     * @param callable $responseGeneratorFn
     * @param bool $purgeExpiredResponse
     * @return never
     * @throws ResponseFromCacheException
     * @throws \Throwable
     */
    protected function sendCacheableResponse(
        ResponseCache $cacheable,
        callable      $responseGeneratorFn,
        bool          $purgeExpiredResponse = false
    ): never
    {
        try {
            $cached = $cacheable->getCached();
        } catch (ResponseInvalidatedException) {
            unset($cached);
            if ($purgeExpiredResponse) {
                try {
                    $cacheable->deleteCached();
                } catch (\Exception $e) {
                    Diagnostics::app()->warning("Failed to DELETE expired cached response", exception: $e);
                }
            }
        } catch (\Exception $e) {
            Diagnostics::app()->warning("Failed to retrieve cached response: " . $e::class, exception: $e);
        }

        if (isset($cached) && $cached instanceof AbstractResponse &&
            is_a($cached, $cacheable->context->responseClassname)) {
            $this->sendResponseFromCache($cacheable, $cached, true);
        }

        try {
            call_user_func($responseGeneratorFn);
        } catch (ResponseFinalizedException) {
            // Add any exception that indicates a response was successfully generated
        }

        try {
            $cacheable->saveCachedResponse($this->getResponseObject());
        } catch (\Exception $e) {
            Diagnostics::app()->warning("Failed to STORE cached response: " . $e::class, exception: $e);
        }

        throw new ResponseFromCacheException();
    }

    /**
     * @param ResponseCacheContext $context
     * @param bool $throwEx
     * @param HttpInterface|null $interface
     * @return void
     * @throws \Charcoal\Base\Exceptions\WrappedException
     */
    protected function purgeCacheableResponse(
        ResponseCacheContext $context,
        bool                 $throwEx,
        ?HttpInterface       $interface = null
    ): void
    {
        try {
            $this->getCacheableResponse($context, $interface)->deleteCached();
        } catch (\Exception $e) {
            if ($throwEx) {
                throw $e;
            }

            Diagnostics::app()->warning("Failed to DELETE cached response: " . $e::class, exception: $e);
        }
    }
}