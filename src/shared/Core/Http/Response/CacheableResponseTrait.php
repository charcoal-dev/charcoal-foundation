<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\Http\AppAwareEndpoint;
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
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    protected function getCacheableResponse(
        CacheSource   $cacheSource,
        string        $uniqueRequestId,
        ?CacheControl $cacheControl,
        ?CacheStore   $cacheStore,
        callable      $responseGeneratorFn,
    ): void
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

        call_user_func($responseGeneratorFn);

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
    }
}