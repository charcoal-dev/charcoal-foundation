<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\CharcoalApp;
use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Foundation\Http\HttpInterface;
use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Directory;
use Charcoal\Filesystem\Exception\FilesystemError;
use Charcoal\Filesystem\Exception\FilesystemException;
use Charcoal\Http\Commons\WritableHeaders;
use Charcoal\Http\Commons\WritablePayload;
use Charcoal\Http\Router\Controllers\CacheControl;
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
     * @param string $uniqueRequestId
     * @param CacheControl|null $cacheControl
     */
    public function __construct(
        AppAwareEndpoint              $route,
        public readonly string        $uniqueRequestId,
        public readonly ?CacheControl $cacheControl
    )
    {
        $this->app = $route->app;
        $this->interface = $route->interface->enum;
        $this->responseClassname = $route->getResponseObject()::class;
    }

    /**
     * @param CacheStore $cacheStore
     * @return AbstractControllerResponse|null
     * @throws \Charcoal\Cache\Exception\CacheDriverOpException
     * @throws \Charcoal\Cache\Exception\CachedEntityException
     */
    public function getFromCache(CacheStore $cacheStore): ?AbstractControllerResponse
    {
        /** @var AbstractControllerResponse $cached */
        $cached = $this->app->cache->get($cacheStore)
            ->get($this->cachePrefixedKey($this->uniqueRequestId));

        $this->returnCheckInstance($cached);
        return $cached;
    }

    /**
     * @param CacheStore $cacheStore
     * @param AbstractControllerResponse $response
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheException
     */
    public function storeInCache(CacheStore $cacheStore, AbstractControllerResponse $response): void
    {
        $this->app->cache->get($cacheStore)
            ->set($this->cachePrefixedKey($this->uniqueRequestId), $response);
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
    public function storeInFilesystem(AbstractControllerResponse $response): void
    {
        $tmpDir = $this->getFilesystemDirectory();
        if (!$tmpDir->isWritable()) {
            throw new \RuntimeException("Cannot write to HTTP interface cache directory");
        }

        $tmpDir->writeToFile(
            $this->uniqueRequestId,
            $this->getSerializedResponse($response),
            append: false,
        );
    }

    /**
     * @return AbstractControllerResponse|null
     * @throws FilesystemException
     */
    public function getFromFilesystem(): ?AbstractControllerResponse
    {
        try {
            $tmpDir = $this->getFilesystemDirectory();
            $response = $tmpDir->getFile(
                $this->uniqueRequestId,
                createIfNotExists: false
            );

            $response = unserialize($response->read(), ["allowed_classes" => [
                AbstractControllerResponse::class,
                PayloadResponse::class,
                BodyResponse::class,
                Buffer::class,
                FileDownloadResponse::class,
                WritableHeaders::class,
                WritablePayload::class
            ]]);

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
     * @param object $result
     * @return void
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