<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\CharcoalApp;
use App\Shared\Exception\CacheableResponseRedundantException;
use App\Shared\Foundation\Http\HttpInterface;
use Charcoal\Filesystem\Directory;
use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;
use Charcoal\Http\Router\Controllers\Response\AbstractControllerResponse;

/**
 * Class CacheableResponse
 * @package App\Shared\Core\Http\Response
 */
readonly class CacheableResponse
{
    /**
     * @param CharcoalApp $app
     * @param HttpInterface $interface
     * @param CacheableResponseContext $context
     */
    public function __construct(
        private CharcoalApp             $app,
        private HttpInterface           $interface,
        public CacheableResponseContext $context,
    )
    {
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
        if ($this->context->source === CacheSource::NONE) {
            return null;
        }

        return $this->context->cacheStore ?
            $this->getFromCacheStore() : $this->getFromFilesystem($this->context->responseUnserializeClasses);
    }

    /**
     * @return void
     * @throws FilesystemException
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     */
    public function deleteCached(): void
    {
        $this->context->cacheStore ?
            $this->deleteFromCacheStore() : $this->deleteFromFilesystem();
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
     * @return AbstractControllerResponse|null
     * @throws CacheableResponseRedundantException
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     * @throws \Charcoal\Cache\Exception\CachedEntityException
     */
    protected function getFromCacheStore(): ?AbstractControllerResponse
    {
        /** @var AbstractControllerResponse $cached */
        $cached = $this->app->cache->get($this->context->cacheStore)
            ->get($this->cachePrefixedKey($this->context->uniqueRequestId));

        $this->returnCheckInstance($cached);
        return $cached;
    }

    /**
     * @param AbstractControllerResponse $response
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheException
     */
    protected function storeInCacheStore(AbstractControllerResponse $response): void
    {
        $this->app->cache->get($this->context->cacheStore)
            ->set($this->cachePrefixedKey($this->context->uniqueRequestId), $response);
    }

    /**
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     */
    protected function deleteFromCacheStore(): void
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
    protected function storeInFilesystem(AbstractControllerResponse $response): void
    {
        $tmpDir = $this->getFilesystemDirectory();
        if (!$tmpDir->isWritable()) {
            throw new \RuntimeException("Cannot write to HTTP interface cache directory");
        }

        $tmpDir->writeToFile(
            $this->context->uniqueRequestId,
            $this->getSerializedResponse($response),
            append: false,
        );
    }

    /**
     * @param class-string[] $allowedClasses
     * @return AbstractControllerResponse|null
     * @throws CacheableResponseRedundantException
     * @throws FilesystemException
     */
    protected function getFromFilesystem(array $allowedClasses = []): ?AbstractControllerResponse
    {
        try {
            $tmpDir = $this->getFilesystemDirectory();
            $response = $tmpDir->getFile(
                $this->context->uniqueRequestId,
                createIfNotExists: false
            );

            $response = unserialize($response->read(), ["allowed_classes" => $allowedClasses]);

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
        $this->getFilesystemDirectory()->delete($this->context->uniqueRequestId);
    }

    /**
     * @param object $result
     * @return void
     * @throws CacheableResponseRedundantException
     */
    private function returnCheckInstance(object $result): void
    {
        if (!$result instanceof AbstractControllerResponse || !is_a($result, $this->context->responseClassname)) {
            throw new \RuntimeException(
                sprintf('Expected cached response of type "%s", got "%s"',
                    $this->context->responseClassname,
                    get_class($result)
                )
            );
        }

        if ($this->context->validity > 0) {
            if ((time() - $result->createdOn) >= $this->context->validity) {
                throw new CacheableResponseRedundantException();
            }
        }

        if ($this->context->integrityTag) {
            if (!$result->getIntegrityTag() || $result->getIntegrityTag() !== $this->context->integrityTag) {
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