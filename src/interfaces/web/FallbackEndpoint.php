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
     */
    protected function entrypoint(): void
    {
        // Browser/CDN side caching:
        $this->useCacheControl(CacheControlHelper::publicCdnCache(3600, 21600));

        trigger_error("Fallback endpoint called", E_USER_WARNING);

        $this->sendTemplate("fallback", [
            "appClassname" => OOP::baseClassName($this->app::class),
            "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
            "modulesLoaded" => array_keys($this->app->build->modulesClasses)
        ]);
    }
}