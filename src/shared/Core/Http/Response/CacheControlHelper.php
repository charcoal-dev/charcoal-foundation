<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use Charcoal\Http\Router\Enums\CacheStoreDirective;
use Charcoal\Http\Router\Response\Headers\CacheControl;

/**
 * Class CacheControlHelper
 * @package App\Shared\Core\Http
 */
class CacheControlHelper
{
    /**
     * @return CacheControl
     * @api
     */
    public static function serverCacheOnly(): CacheControl
    {
        return new CacheControl(CacheStoreDirective::NO_STORE, mustRevalidate: true, noCache: true);
    }

    /**
     * @param int $maxAge
     * @param int $cdnMaxAge
     * @return CacheControl
     * @api
     */
    public static function publicCdnCache(int $maxAge, int $cdnMaxAge): CacheControl
    {
        return new CacheControl(CacheStoreDirective::PUBLIC, maxAge: $maxAge, sMaxAge: $cdnMaxAge, mustRevalidate: true);
    }

    /**
     * @param int $maxAge
     * @return CacheControl
     * @api
     */
    public static function privateBrowserOnly(int $maxAge): CacheControl
    {
        return new CacheControl(CacheStoreDirective::PRIVATE, maxAge: $maxAge, mustRevalidate: true);
    }
}