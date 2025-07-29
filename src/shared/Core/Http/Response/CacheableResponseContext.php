<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use App\Shared\Context\CacheStore;
use Charcoal\Http\Router\Controllers\CacheControl;

/**
 * Class CacheableResponseBinding
 * @package App\Shared\Core\Http\Response
 */
readonly class CacheableResponseContext
{
    /**
     * @param string $uniqueRequestId
     * @param CacheSource $source
     * @param CacheStore|null $cacheStore
     * @param CacheControl|null $cacheControlHeader
     * @param int $validity
     * @param string|null $integrityTag
     * @param class-string $responseClassname
     * @param class-string[] $responseUnserializeClasses
     */
    public function __construct(
        public string        $uniqueRequestId,
        public CacheSource   $source,
        public ?CacheStore   $cacheStore,
        public ?CacheControl $cacheControlHeader,
        public int           $validity,
        public ?string       $integrityTag,
        public string        $responseClassname,
        public array         $responseUnserializeClasses = []
    )
    {
        if ($this->source === CacheSource::CACHE && !$this->cacheStore) {
            throw new \LogicException("CacheStore is required when source is Cache");
        }
    }
}