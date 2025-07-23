<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\Context\CacheStore;
use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Exception\ApiResponseFinalizedException;
use App\Shared\Exception\CacheableResponseSuccessException;
use Charcoal\Http\Router\Controllers\CacheControl;

/**
 * Trait CacheableResponseTrait
 * @package App\Shared\Core\Http\Response
 * @mixin AppAwareEndpoint
 */
trait CacheableResponseTrait
{
    /**
     * @param CacheSource $cacheSource
     * @param string $uniqueRequestId
     * @param CacheControl|null $cacheControl
     * @param CacheStore|null $cacheStore
     * @param callable $responseGeneratorFn
     * @return never
     * @throws CacheableResponseSuccessException
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     * @throws \Throwable
     */
    protected function sendCacheableResponse(
        CacheSource   $cacheSource,
        string        $uniqueRequestId,
        ?CacheControl $cacheControl,
        ?CacheStore   $cacheStore,
        callable      $responseGeneratorFn,
    ): never
    {
        if ($cacheSource === CacheSource::CACHE && !$cacheStore) {
            throw new \LogicException("No cache storage provided for cacheable response");
        }

        if ($cacheSource !== CacheSource::NONE) {
            $cacheable = new CacheableResponse($this, $uniqueRequestId, $cacheControl);
            try {
                $cached = $cacheStore ?
                    $cacheable->getFromCache($cacheStore) : $cacheable->getFromFilesystem();
            } catch (\Exception $e) {
                $errorMsg = "Failed to retrieve cached response: " . $e::class;
                trigger_error($errorMsg, E_USER_NOTICE);
                $this->app->lifecycle->exception(new \RuntimeException($errorMsg, previous: $e));
            }

            if (isset($cached)) {
                $this->sendResponseFromCache($cacheable, $cached, true);
            }
        }

        try {
            call_user_func($responseGeneratorFn);
        } catch (ApiResponseFinalizedException) {
            // Add any exception that indicates a response was successfully generated
        } catch (\Throwable $t) {
            // Re-throw any caught error, preventing the error itself from being cached
            throw $t;
        }

        if (isset($cacheable)) {
            try {
                if ($cacheStore) {
                    $cacheable->storeInCache($cacheStore, $this->response());
                } else {
                    $cacheable->storeInFilesystem($this->response());
                }
            } catch (\Exception $e) {
                $errorMsg = "Failed to STORE cached response: " . $e::class;
                trigger_error($errorMsg, E_USER_NOTICE);
                $this->app->lifecycle->exception(new \RuntimeException($errorMsg, previous: $e));
            }
        }

        throw new CacheableResponseSuccessException();
    }
}