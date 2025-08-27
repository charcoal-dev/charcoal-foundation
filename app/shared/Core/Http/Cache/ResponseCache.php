<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Cache;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\Exceptions\Cache\ResponseInvalidatedException;
use App\Shared\Enums\Http\HttpInterface;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Filesystem\Exceptions\PathNotFoundException;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Node\DirectoryNode;
use Charcoal\Http\Router\Response\AbstractResponse;

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
     * @return AbstractResponse|null
     * @throws ResponseInvalidatedException
     * @throws WrappedException
     */
    public function getCached(): ?AbstractResponse
    {
        try {
            if (!$this->context->storage) {
                return null;
            }

            $cachedEntity = $this->context->storageProvider ? $this->getFromCacheStore() :
                $this->getFromFilesystem($this->context->responseUnserializeClasses);
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to retrieve response from cache");
        }

        if (!$cachedEntity instanceof AbstractResponse ||
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
            $this->context->storageProvider ?
                $this->deleteFromCacheStore() : $this->deleteFromFilesystem();
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to delete cached response");
        }
    }

    /**
     * @param AbstractResponse $response
     * @return void
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    public function saveCachedResponse(AbstractResponse $response): void
    {
        if (!$this->context->storage) {
            return;
        }

        $this->context->storageProvider ?
            $this->storeInCacheStore($response) : $this->storeInFilesystem($response);
    }

    /**
     * @return mixed
     * @throws \Charcoal\Cache\Exceptions\CachedEntityException
     */
    private function getFromCacheStore(): mixed
    {
        /** @var AbstractResponse $cached */
        return $this->context->storageProvider->get($this->cachePrefixedKey($this->context->uniqueRequestId));
    }

    /**
     * @param AbstractResponse $response
     * @return void
     */
    private function storeInCacheStore(AbstractResponse $response): void
    {
        $this->context->storageProvider->set($this->cachePrefixedKey($this->context->uniqueRequestId), $response);
    }

    /**
     * @return void
     * @throws \Charcoal\Cache\Exceptions\CacheDriverException
     */
    private function deleteFromCacheStore(): void
    {
        $this->context->storageProvider->delete($this->cachePrefixedKey($this->context->uniqueRequestId));
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
     * @param AbstractResponse $response
     * @return void
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function storeInFilesystem(AbstractResponse $response): void
    {
        $tmpDir = $this->getFilesystemDirectory();
        if (!$tmpDir->path->writable) {
            throw new \RuntimeException("Cannot write to HTTP interface cache directory");
        }

        $tmpDir->file($this->context->uniqueRequestId, true, createIfNotExists: true)
            ->write(serialize($response), false, false);
    }

    /**
     * @param array $allowedClasses
     * @return mixed
     * @throws PathNotFoundException
     * @throws PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function getFromFilesystem(array $allowedClasses = []): mixed
    {
        try {
            $tmpDir = $this->getFilesystemDirectory();
            $response = $tmpDir->file(
                $this->context->uniqueRequestId,
                true,
                createIfNotExists: false
            );

            return unserialize($response->read(), ["allowed_classes" => $allowedClasses]);
        } catch (PathNotFoundException) {
            return null;
        }
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function deleteFromFilesystem(): void
    {
        $this->getFilesystemDirectory()->deleteChild($this->context->uniqueRequestId);
    }

    /**
     * @return DirectoryNode
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    private function getFilesystemDirectory(): DirectoryNode
    {
        return (new DirectoryNode($this->app->paths->tmp))->directory("/cache", true, true);
    }
}