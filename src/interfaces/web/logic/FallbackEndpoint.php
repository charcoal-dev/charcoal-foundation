<?php
declare(strict_types=1);

namespace App\Interfaces\Web;


use App\Shared\Core\Http\HttpInterfaceBinding;
use Charcoal\OOP\OOP;
use Composer\InstalledVersions;

/**
 * Class FallbackEndpoint
 * @package App\Interfaces\Web
 */
class FallbackEndpoint extends AbstractWebEndpoint
{
    protected function entrypoint(): void
    {
        $this->sendTemplate("fallback", [
            "appClassname" => OOP::baseClassName($this->app::class),
            "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
            "modulesLoaded" => array_keys($this->app->build->modulesClasses)
        ]);
    }
}