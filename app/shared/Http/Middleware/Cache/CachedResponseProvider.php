<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Http\Middleware\Cache;

use Charcoal\App\Kernel\Clock\Clock;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\Base\Dataset\KeyValue;
use Charcoal\Base\Support\ErrorHelper;
use Charcoal\Buffers\BufferImmutable;
use Charcoal\Cache\CacheClient;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Http\Commons\Enums\CacheControl;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Contracts\Cache\CacheProviderInterface;
use Charcoal\Http\Server\Request\Cache\CachedResponsePointer;
use Charcoal\Http\Server\Request\Result\CachedResult;
use Charcoal\Http\Server\Request\Result\Response\EncodedBufferResponse;
use Charcoal\Http\Server\Request\Result\Response\FileDownloadResponse;
use Charcoal\Http\Server\Request\Result\Response\NoContentResponse;

/**
 * A class responsible for managing cached HTTP responses. The cache storage can either be
 * a file system directory or a cache client, and it supports operations like retrieval,
 * storage, and deletion of cached entries.
 */
final readonly class CachedResponseProvider implements CacheProviderInterface
{
    private string $namespaceSeparator;

    /**
     * @param DirectoryPath|CacheClient $storage
     * @param string[] $namespaces
     */
    public function __construct(
        public DirectoryPath|CacheClient $storage,
        private array                    $namespaces
    )
    {
        $index = -1;
        foreach ($this->namespaces as $namespace) {
            $index++;
            if (!is_string($namespace) || !preg_match('/\A[a-zA-Z0-9\-_]{2,40}\z/', $namespace)) {
                throw new \InvalidArgumentException("Invalid namespace for cached response PROVIDER at index: "
                    . $index);
            }
        }

        $this->namespaceSeparator = $this->storage instanceof CacheClient ? ":" : DIRECTORY_SEPARATOR;
    }

    /**
     * @param CachedResponsePointer $pointer
     * @return CachedResult|null
     */
    public function get(CachedResponsePointer $pointer): ?CachedResult
    {
        $cacheResultKey = $this->normalizeStorageKey($pointer);

        try {
            // Directory
            if ($this->storage instanceof DirectoryPath) {
                $filePath = $this->storage->join($cacheResultKey)->path;
                error_clear_last();
                if (!@file_exists($filePath) || !@is_readable($filePath)) {
                    $exception = ErrorHelper::lastErrorToRuntimeException();
                    if ($exception) {
                        throw $exception;
                    }

                    return null;
                }

                $cacheResultBuffer = @file_get_contents($filePath);
                if (!$cacheResultBuffer) {
                    $exception = ErrorHelper::lastErrorToRuntimeException();
                    if ($exception) {
                        throw $exception;
                    }

                    return null;
                }

                return $this->handleCacheResultBuffer($cacheResultBuffer, $cacheResultKey);
            }

            // Cache
            if ($this->storage instanceof CacheClient) {
                $cacheResultBuffer = $this->storage->get($this->normalizeStorageKey($pointer));
                if (is_string($cacheResultBuffer) && $cacheResultBuffer) {
                    return $this->handleCacheResultBuffer($cacheResultBuffer, $cacheResultKey);
                }
            }
        } catch (\Exception $e) {
            Diagnostics::app()->warning("Failed to load cached response: " . $e::class,
                exception: $e);
        }

        throw new \RuntimeException("Unsupported HTTP cache storage type");
    }

    /**
     * @param string $buffer
     * @param string $key
     * @return CachedResult|null
     */
    private function handleCacheResultBuffer(string $buffer, string $key): ?CachedResult
    {
        try {
            $unserialized = unserialize($buffer, [
                "allowed_classes" => [
                    CachedResult::class,
                    Headers::class,
                    HeadersImmutable::class,
                    Charset::class,
                    KeyValue::class,
                    \DateTimeImmutable::class,
                    CacheControl::class,
                    CacheControlDirectives::class,
                    NoContentResponse::class,
                    FileDownloadResponse::class,
                    EncodedBufferResponse::class,
                    ContentType::class,
                    BufferImmutable::class,
                ]
            ]);

            if (!$unserialized instanceof CachedResult) {
                throw new \UnexpectedValueException("Expected instance of CachedResult, got: " . gettype($unserialized));
            }

            return $unserialized;
        } catch (\Exception $e) {
            Diagnostics::app()->warning("Failed to unserialize cached response: " . $e::class,
                exception: $e);

            $this->deleteKey($key);
            return null;
        }
    }

    public function store(CachedResponsePointer $pointer, CachedResult $result): void
    {
        $cacheResultKey = $this->normalizeStorageKey($pointer);
        try {
            $cacheBuffer = serialize($result);
            if ($this->storage instanceof DirectoryPath) {
                $filePath = $this->storage->join($cacheResultKey)->path;
                error_clear_last();
                if (!@file_put_contents($filePath, $cacheBuffer, LOCK_EX)) {
                    $exception = ErrorHelper::lastErrorToRuntimeException();
                    if ($exception) {
                        throw $exception;
                    }
                }
            }

            if ($this->storage instanceof CacheClient) {
                $this->storage->set($cacheResultKey, $cacheBuffer, $pointer->validity);
            }
        } catch (\Exception $e) {
            Diagnostics::app()->warning("Failed to store cached response: " . $e::class,
                exception: $e);
        }
    }

    /**
     * @param CachedResponsePointer $pointer
     * @return void
     */
    public function delete(CachedResponsePointer $pointer): void
    {
        $this->deleteKey($this->normalizeStorageKey($pointer));
    }

    /**
     * @param string $cacheResultKey
     * @return void
     */
    private function deleteKey(string $cacheResultKey): void
    {
        try {
            // Directory
            if ($this->storage instanceof DirectoryPath) {
                $filePath = $this->storage->join($cacheResultKey)->path;
                error_clear_last();
                if (!@unlink($filePath)) {
                    $exception = ErrorHelper::lastErrorToRuntimeException();
                    if ($exception) {
                        throw $exception;
                    }
                }

                return;
            }

            // Cache
            if ($this->storage instanceof CacheClient) {
                $this->storage->delete($cacheResultKey);
            }
        } catch (\Exception $e) {
            Diagnostics::app()->warning("Failed to delete cached response: " . $e::class,
                exception: $e);
        }

        throw new \RuntimeException("Unsupported HTTP cache storage type");
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getTimestamp(): \DateTimeImmutable
    {
        return Clock::now();
    }

    /**
     * @param CachedResponsePointer $pointer
     * @return string
     */
    private function normalizeStorageKey(CachedResponsePointer $pointer): string
    {
        return implode($this->namespaceSeparator,
            array_merge($this->namespaces, $pointer->namespaces, [$pointer->uniqueId]));
    }
}