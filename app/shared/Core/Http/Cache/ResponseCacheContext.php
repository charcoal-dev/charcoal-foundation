<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Cache;

use Charcoal\Base\Enums\StorageType;
use Charcoal\Cache\CacheClient;
use Charcoal\Http\Router\Response\Headers\CacheControl;

/**
 * Class ResponseCacheContext
 * @package App\Shared\Core\Http\Cache
 */
readonly class ResponseCacheContext
{
    /**
     * @param class-string $responseClassname
     * @param class-string[] $responseUnserializeClasses
     */
    public function __construct(
        public string        $uniqueRequestId,
        public StorageType   $storage,
        public ?CacheClient  $storageProvider,
        public ?CacheControl $cacheControlHeader,
        public int           $validity,
        public ?string       $integrityTag,
        public string        $responseClassname,
        public array         $responseUnserializeClasses = []
    )
    {
        match ($this->storage) {
            StorageType::Filesystem,
            StorageType::Cache => $this->validateStorageType(),
            default => throw new \LogicException("Storage type not supported"),
        };
    }

    /**
     * @return void
     */
    private function validateStorageType(): void
    {
        if ($this->storageProvider && $this->storageProvider->storageType() !== $this->storage) {
            throw new \LogicException("StorageProvider storage type does not match ResponseCacheContext storage type");
        }

        if ($this->storage === StorageType::Filesystem) {
            if ($this->storageProvider) {
                throw new \LogicException("StorageProvider must be NULL for Filesystem storage");
            }
        } elseif ($this->storage === StorageType::Cache) {
            if (!$this->storageProvider) {
                throw new \LogicException("StorageProvider must be set for Cache storage");
            }
        }
    }
}