<?php
declare(strict_types=1);

namespace App\Interfaces\Web;

use App\Shared\Core\Http\Response\CacheControlHelper;
use Charcoal\Base\Support\Helpers\ObjectHelper;
use Composer\InstalledVersions;

/**
 * Handles fallback rendering for the application.
 */
class FallbackEndpoint extends AbstractWebEndpoint
{
    protected function entrypoint(): void
    {
        // Browser/CDN side caching:
        $this->setCacheControl(CacheControlHelper::publicCdnCache(3600, 21600));

        $this->sendTemplate("fallback", [
            "appClassname" => ObjectHelper::baseClassName($this->app::class),
            "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
            "modulesLoaded" => array_keys($this->app->context->domain)
        ]);
    }
}