<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Exception\ApiResponseFinalizedException;
use App\Shared\Exception\CacheableResponseRedundantException;
use App\Shared\Exception\CacheableResponseSuccessException;
use App\Shared\Foundation\Http\HttpInterface;
use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;

/**
 * Trait CacheableResponseTrait
 * @package App\Shared\Core\Http\Response
 * @mixin AppAwareEndpoint
 */
trait CacheableResponseTrait
{
    /**
     * @param CacheableResponseContext $context
     * @param HttpInterface|null $interface
     * @return CacheableResponse
     */
    protected function getCacheableResponse(
        CacheableResponseContext $context,
        ?HttpInterface           $interface = null
    ): CacheableResponse
    {
        return new CacheableResponse($this->app, $interface ?? $this->interface->enum, $context);
    }

    /**
     * @param CacheableResponse $cacheable
     * @param callable $responseGeneratorFn
     * @param bool $purgeExpiredResponse
     * @return never
     * @throws CacheableResponseSuccessException
     * @throws \Throwable
     */
    protected function sendCacheableResponse(
        CacheableResponse $cacheable,
        callable          $responseGeneratorFn,
        bool              $purgeExpiredResponse = false
    ): never
    {
        try {
            $cached = $cacheable->getCached();
        } catch (CacheableResponseRedundantException) {
            unset($cached);
            if ($purgeExpiredResponse) {
                try {
                    $cacheable->deleteCached();
                } catch (\Exception $e) {
                    $this->app->lifecycle->exception($e);
                }
            }
        } catch (\Exception $e) {
            $errorMsg = "Failed to retrieve cached response: " . $e::class;
            trigger_error($errorMsg, E_USER_NOTICE);
            $this->app->lifecycle->exception(new \RuntimeException($errorMsg, previous: $e));
        }

        if (isset($cached) && $cached instanceof AbstractControllerResponse &&
            is_a($cached, $cacheable->context->responseClassname)) {
            $this->sendResponseFromCache($cacheable, $cached, true);
        }

        try {
            call_user_func($responseGeneratorFn);
        } catch (ApiResponseFinalizedException) {
            // Add any exception that indicates a response was successfully generated
        } catch (\Throwable $t) {
            // Re-throw any caught error, preventing the error itself from being cached
            throw $t;
        }

        try {
            $cacheable->saveCachedResponse($this->response());
        } catch (\Exception $e) {
            $errorMsg = "Failed to STORE cached response: " . $e::class;
            trigger_error($errorMsg, E_USER_NOTICE);
            $this->app->lifecycle->exception(new \RuntimeException($errorMsg, previous: $e));
        }

        throw new CacheableResponseSuccessException();
    }

    /**
     * @param CacheableResponseContext $context
     * @param bool $throwEx
     * @param HttpInterface|null $interface
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    protected function purgeCacheableResponse(
        CacheableResponseContext $context,
        bool                     $throwEx,
        ?HttpInterface           $interface = null
    ): void
    {
        try {
            try {
                $this->getCacheableResponse($context, $interface)->deleteCached();
            } catch (FilesystemException $e) {
                if ($e->error !== FilesystemError::PATH_NOT_EXISTS) {
                    throw $e;
                }
            }
        } catch (\Exception $e) {
            if ($throwEx) {
                throw $e;
            }

            $errorMsg = "Failed to DELETE cached response: " . $e::class;
            trigger_error($errorMsg, E_USER_NOTICE);
            $this->app->lifecycle->exception(new \RuntimeException($errorMsg, previous: $e));
        }
    }
}