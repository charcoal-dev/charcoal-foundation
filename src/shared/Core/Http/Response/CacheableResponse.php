<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Exception\CacheableResponseRedundantException;
use App\Shared\Foundation\Http\HttpInterface;
use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Directory;
use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;
use Charcoal\Http\Commons\KeyValuePair;
use Charcoal\Http\Commons\WritableHeaders;
use Charcoal\Http\Commons\WritablePayload;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;
use Charcoal\Http\Router\Controllers\Response\BodyResponse;
use Charcoal\Http\Router\Controllers\Response\FileDownloadResponse;
use Charcoal\Http\Router\Controllers\Response\PayloadResponse;

/**
 * Class CacheableResponse
 * @package App\Shared\Core\Http\Response
 */
class CacheableResponse
{
    private CharcoalApp $app;
    private readonly HttpInterface $interface;
    private readonly string $responseClassname;

    /**
     * @param AppAwareEndpoint $route
     * @param CacheableResponseBinding $binding
     */
    public function __construct(
        AppAwareEndpoint                         $route,
        public readonly CacheableResponseBinding $binding,
    )
    {
        $this->app = $route->app;
        $this->interface = $route->interface->enum;
        $this->responseClassname = $route->getResponseObject()::class;
    }

    /**
     * @return AbstractControllerResponse|null
     * @throws CacheableResponseRedundantException
     * @throws FilesystemException
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     * @throws \Charcoal\Cache\Exception\CachedEntityException
     */
    public function getCached(): ?AbstractControllerResponse
    {
        if ($this->binding->source === CacheSource::NONE) {
            return null;
        }

        return $this->binding->cacheStore ?
            $this->getFromCache() : $this->getFromFilesystem($this->binding->responseUnserializeClasses);
    }

    /**
     * @return void
     * @throws FilesystemException
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     */
    public function deleteCached(): void
    {
        $this->binding->cacheStore ?
            $this->deleteFromCache() : $this->deleteFromFilesystem();
    }

    /**
     * @param AbstractControllerResponse $response
     * @return void
     * @throws FilesystemException
     * @throws \Charcoal\Cache\Exception\CacheException
     */
    public function cacheResponse(AbstractControllerResponse $response): void
    {
        if ($this->binding->source === CacheSource::NONE) {
            return;
        }

        $this->binding->cacheStore ?
            $this->storeInCache($response) : $this->storeInFilesystem($response);
    }

    /**
     * @return AbstractControllerResponse|null
     * @throws CacheableResponseRedundantException
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     * @throws \Charcoal\Cache\Exception\CachedEntityException
     */
    protected function getFromCache(): ?AbstractControllerResponse
    {
        /** @var AbstractControllerResponse $cached */
        $cached = $this->app->cache->get($this->binding->cacheStore)
            ->get($this->cachePrefixedKey($this->binding->uniqueRequestId));

        $this->returnCheckInstance($cached);
        return $cached;
    }

    /**
     * @param AbstractControllerResponse $response
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheException
     */
    protected function storeInCache(AbstractControllerResponse $response): void
    {
        $this->app->cache->get($this->binding->cacheStore)
            ->set($this->cachePrefixedKey($this->binding->uniqueRequestId), $response);
    }

    /**
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     */
    protected function deleteFromCache(): void
    {
        $this->app->cache->get($this->binding->cacheStore)
            ->delete($this->cachePrefixedKey($this->binding->uniqueRequestId));
    }

    /**
     * @param string $key
     * @return string
     */
    private function cachePrefixedKey(string $key): string
    {
        return "http_cache:" . $this->interface->value . ":" . $key;
    }

    /**
     * @param AbstractControllerResponse $response
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    protected function storeInFilesystem(AbstractControllerResponse $response): void
    {
        $tmpDir = $this->getFilesystemDirectory();
        if (!$tmpDir->isWritable()) {
            throw new \RuntimeException("Cannot write to HTTP interface cache directory");
        }

        $tmpDir->writeToFile(
            $this->binding->uniqueRequestId,
            $this->getSerializedResponse($response),
            append: false,
        );
    }

    /**
     * @param array $additionalAllowedClasses
     * @return AbstractControllerResponse|null
     * @throws CacheableResponseRedundantException
     * @throws FilesystemException
     */
    protected function getFromFilesystem(array $additionalAllowedClasses = []): ?AbstractControllerResponse
    {
        try {
            $tmpDir = $this->getFilesystemDirectory();
            $response = $tmpDir->getFile(
                $this->binding->uniqueRequestId,
                createIfNotExists: false
            );

            $response = unserialize($response->read(), ["allowed_classes" => array_merge([
                AbstractControllerResponse::class,
                PayloadResponse::class,
                BodyResponse::class,
                Buffer::class,
                FileDownloadResponse::class,
                WritableHeaders::class,
                WritablePayload::class,
                KeyValuePair::class
            ], $additionalAllowedClasses)]);

            $this->returnCheckInstance($response);
            return $response;
        } catch (FilesystemException $e) {
            if (in_array($e->error, [FilesystemError::PATH_NOT_EXISTS, FilesystemError::PATH_TYPE_ERR])) {
                return null;
            }

            throw $e;
        }
    }

    /**
     * @return void
     * @throws FilesystemException
     */
    protected function deleteFromFilesystem(): void
    {
        $this->getFilesystemDirectory()->delete($this->binding->uniqueRequestId);
    }

    /**
     * @param object $result
     * @return void
     * @throws CacheableResponseRedundantException
     */
    private function returnCheckInstance(object $result): void
    {
        if (!$result instanceof AbstractControllerResponse || !is_a($result, $this->responseClassname)) {
            throw new \RuntimeException(
                sprintf('Expected cached response of type "%s", got "%s"',
                    $this->responseClassname,
                    get_class($result)
                )
            );
        }

        if ($this->binding->validity > 0) {
            if ((time() - $result->createdOn) >= $this->binding->validity) {
                throw new CacheableResponseRedundantException();
            }
        }

        if ($this->binding->integrityTag) {
            if (!$result->getIntegrityTag() || $result->getIntegrityTag() !== $this->binding->integrityTag) {
                throw new CacheableResponseRedundantException();
            }
        }
    }

    /**
     * @param AbstractControllerResponse $response
     * @return string
     */
    private function getSerializedResponse(AbstractControllerResponse $response): string
    {
        return serialize($response);
    }

    /**
     * @return Directory
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    private function getFilesystemDirectory(): Directory
    {
        return $this->app->directories->tmp->getDirectory("cache/" . $this->interface->value, true);
    }
}