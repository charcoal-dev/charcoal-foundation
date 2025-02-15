<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Http\Router\Controllers\CacheControl;
use Charcoal\Http\Router\Controllers\CacheStoreDirective;

/**
 * Class CacheControlHelper
 * @package App\Shared\Core\Http
 */
class CacheControlHelper
{
    /**
     * @return CacheControl
     */
    public static function serverCacheOnly(): CacheControl
    {
        return new CacheControl(CacheStoreDirective::NO_STORE, mustRevalidate: true, noCache: true);
    }

    /**
     * @param int $maxAge
     * @param int $cdnMaxAge
     * @return CacheControl
     */
    public static function publicCdnCache(int $maxAge, int $cdnMaxAge): CacheControl
    {
        return new CacheControl(CacheStoreDirective::PUBLIC, maxAge: $maxAge, sMaxAge: $cdnMaxAge, mustRevalidate: true);
    }

    /**
     * @param int $maxAge
     * @return CacheControl
     */
    public static function privateBrowserOnly(int $maxAge): CacheControl
    {
        return new CacheControl(CacheStoreDirective::PRIVATE, maxAge: $maxAge, mustRevalidate: true);
    }
}