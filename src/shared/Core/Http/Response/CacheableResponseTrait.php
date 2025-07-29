<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Exception\ApiResponseFinalizedException;
use App\Shared\Exception\CacheableResponseRedundantException;
use App\Shared\Exception\CacheableResponseSuccessException;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;

/**
 * Trait CacheableResponseTrait
 * @package App\Shared\Core\Http\Response
 * @mixin AppAwareEndpoint
 */
trait CacheableResponseTrait
{
    private ?CacheableResponse $cacheableResponse = null;

    /**
     * @return CacheableResponseBinding
     */
    abstract protected function declareCacheableResponseBinding(): CacheableResponseBinding;

    /**
     * @return CacheableResponse
     */
    protected function getCacheableResponse(): CacheableResponse
    {
        if ($this->cacheableResponse) {
            return $this->cacheableResponse;
        }

        if (!$this instanceof CacheableResponseInterface) {
            throw new \LogicException("Endpoint class does not implement CacheableResponseInterface");
        }

        if (!isset($this->cacheableResponseBinding)) {
            throw new \LogicException("CacheableResponseBinding not declared");
        }

        if ($this->cacheableResponseBinding->source === CacheSource::CACHE &&
            !$this->cacheableResponseBinding->cacheStore) {
            throw new \LogicException("No cache store provided for cacheable response");
        }

        return $this->cacheableResponse = new CacheableResponse($this, $this->cacheableResponseBinding);
    }


    /**
     * @param callable $responseGeneratorFn
     * @param bool $purgeExpiredResponse
     * @return never
     * @throws CacheableResponseSuccessException
     * @throws \Throwable
     */
    protected function sendCacheableResponse(
        callable $responseGeneratorFn,
        bool     $purgeExpiredResponse = false
    ): never
    {
        $cacheable = $this->getCacheableResponse();

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
            is_a($cached, $this->cacheableResponseBinding->responseClassname)) {
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
            $cacheable->cacheResponse($this->response());
        } catch (\Exception $e) {
            $errorMsg = "Failed to STORE cached response: " . $e::class;
            trigger_error($errorMsg, E_USER_NOTICE);
            $this->app->lifecycle->exception(new \RuntimeException($errorMsg, previous: $e));
        }

        throw new CacheableResponseSuccessException();
    }
}