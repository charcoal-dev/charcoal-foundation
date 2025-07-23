<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

/**
 * Class CacheableResponseBinding
 * @package App\Shared\Core\Http\Response
 */
readonly class CacheableResponseBinding
{
    /**
     * @param class-string $responseClassname
     * @param class-string[] $responseUnserializeClasses
     */
    public function __construct(
        public string $responseClassname,
        public array  $responseUnserializeClasses = []
    )
    {
    }
}