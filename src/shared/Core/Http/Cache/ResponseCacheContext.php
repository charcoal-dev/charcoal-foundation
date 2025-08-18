<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Cache;

use Charcoal\Base\Contracts\Storage\StorageProviderInterface;
use Charcoal\Base\Enums\StorageType;
use Charcoal\Http\Router\Controllers\CacheControl;

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
        public string                    $uniqueRequestId,
        public StorageType               $storage,
        public ?StorageProviderInterface $storageProvider,
        public ?CacheControl             $cacheControlHeader,
        public int                       $validity,
        public ?string                   $integrityTag,
        public string                    $responseClassname,
        public array                     $responseUnserializeClasses = []
    )
    {
        match ($this->storage) {
            StorageType::FILESYSTEM,
            StorageType::CACHE => $this->validateStorageType(),
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

        if ($this->storage === StorageType::FILESYSTEM) {
            if ($this->storageProvider) {
                throw new \LogicException("StorageProvider must be NULL for Filesystem storage");
            }
        } elseif ($this->storage === StorageType::CACHE) {
            if (!$this->storageProvider) {
                throw new \LogicException("StorageProvider must be set for Cache storage");
            }
        }
    }
}