<?php
declare(strict_types=1);

namespace App\Interfaces\Web;

use App\Shared\Core\Http\Response\CacheControlHelper;
use Charcoal\OOP\OOP;
use Composer\InstalledVersions;

/**
 * Class FallbackEndpoint
 * @package App\Interfaces\Web
 */
class FallbackEndpoint extends AbstractWebEndpoint
{
    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Http\Router\Exception\ResponseDispatchedException
     */
    protected function entrypoint(): void
    {
        // Browser/CDN side caching:
        $this->useCacheControl(CacheControlHelper::publicCdnCache(3600, 21600));


        // Remove below lines, private caching:
        $cacheable = $this->getCacheableResponse("fallback", CacheControlHelper::publicCdnCache(86400, 432000));
        $cached = $cacheable->getFromFilesystem();
        if ($cached) {
            $this->sendResponseFromCache($cacheable, $cached, true);
        }

        $this->sendTemplate("fallback", [
            "appClassname" => OOP::baseClassName($this->app::class),
            "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
            "modulesLoaded" => array_keys($this->app->build->modulesClasses)
        ]);

        $cacheable->storeInFilesystem($this->response());
    }
}