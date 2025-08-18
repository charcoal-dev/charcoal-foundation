<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Cache;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\Exception\Cache\ResponseInvalidatedException;
use App\Shared\Core\Http\Response\CacheSource;
use App\Shared\Foundation\Http\HttpInterface;
use Charcoal\Base\Exception\WrappedException;
use Charcoal\Filesystem\Directory;
use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;

/**
 * Class ResponseCache
 * @package App\Shared\Core\Http\Cache
 */
readonly class ResponseCache
{
    /**
     * @param CharcoalApp $app
     * @param HttpInterface $interface
     * @param ResponseCacheContext $context
     */
    public function __construct(
        private CharcoalApp         $app,
        private HttpInterface       $interface,
        public ResponseCacheContext $context,
    )
    {
    }

    /**
     * @return AbstractControllerResponse|null
     * @throws ResponseInvalidatedException
     * @throws WrappedException
     */
    public function getCached(): ?AbstractControllerResponse
    {
        try {
            if ($this->context->source === CacheSource::NONE) {
                return null;
            }

            $cachedEntity = $this->context->cacheStore ? $this->getFromCacheStore() :
                $this->getFromFilesystem($this->context->responseUnserializeClasses);
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to retrieve response from cache");
        }

        if (!$cachedEntity instanceof AbstractControllerResponse ||
            !is_a($cachedEntity, $this->context->responseClassname)) {
            throw new WrappedException(new \RuntimeException(sprintf(
                'Expected cached response of type "%s", got "%s"',
                $this->context->responseClassname,
                get_class($cachedEntity))),
                message: "Cached response is not of expected type"
            );
        }

        if ($this->context->validity > 0) {
            if ((time() - $cachedEntity->createdOn) >= $this->context->validity) {
                throw new ResponseInvalidatedException();
            }
        }

        if ($this->context->integrityTag) {
            if (!$cachedEntity->getIntegrityTag() ||
                $cachedEntity->getIntegrityTag() !== $this->context->integrityTag) {
                throw new ResponseInvalidatedException();
            }
        }

        return $cachedEntity;
    }

    /**
     * @return void
     * @throws WrappedException
     */
    public function deleteCached(): void
    {
        try {
            $this->context->cacheStore ?
                $this->deleteFromCacheStore() : $this->deleteFromFilesystem();
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to delete cached response");
        }
    }

    /**
     * @param AbstractControllerResponse $response
     * @return void
     * @throws FilesystemException
     * @throws \Charcoal\Cache\Exception\CacheException
     */
    public function saveCachedResponse(AbstractControllerResponse $response): void
    {
        if ($this->context->source === CacheSource::NONE) {
            return;
        }

        $this->context->cacheStore ?
            $this->storeInCacheStore($response) : $this->storeInFilesystem($response);
    }

    /**
     * @return mixed
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     * @throws \Charcoal\Cache\Exception\CachedEntityException
     */
    private function getFromCacheStore(): mixed
    {
        /** @var AbstractControllerResponse $cached */
        return $this->app->cache->get($this->context->cacheStore)
            ->get($this->cachePrefixedKey($this->context->uniqueRequestId));
    }

    /**
     * @param AbstractControllerResponse $response
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheException
     */
    private function storeInCacheStore(AbstractControllerResponse $response): void
    {
        $this->app->cache->get($this->context->cacheStore)
            ->set($this->cachePrefixedKey($this->context->uniqueRequestId), $response);
    }

    /**
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     */
    private function deleteFromCacheStore(): void
    {
        $this->app->cache->get($this->context->cacheStore)
            ->delete($this->cachePrefixedKey($this->context->uniqueRequestId));
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
    private function storeInFilesystem(AbstractControllerResponse $response): void
    {
        $tmpDir = $this->getFilesystemDirectory();
        if (!$tmpDir->isWritable()) {
            throw new \RuntimeException("Cannot write to HTTP interface cache directory");
        }

        $tmpDir->writeToFile($this->context->uniqueRequestId,
            serialize($response),
            append: false);
    }

    /**
     * @param array $allowedClasses
     * @return mixed
     * @throws FilesystemException
     */
    private function getFromFilesystem(array $allowedClasses = []): mixed
    {
        try {
            $tmpDir = $this->getFilesystemDirectory();
            $response = $tmpDir->getFile(
                $this->context->uniqueRequestId,
                createIfNotExists: false
            );

            return unserialize($response->read(), ["allowed_classes" => $allowedClasses]);
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
    private function deleteFromFilesystem(): void
    {
        $this->getFilesystemDirectory()->delete($this->context->uniqueRequestId);
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