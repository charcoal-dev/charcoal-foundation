<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Web\Endpoints;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Http\Server\Attributes\DefaultEntrypoint;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Composer\InstalledVersions;

/**
 * The `FallbackPage` class is responsible for rendering a fallback template
 * when no specific page or controller can handle the request. It gathers
 * information such as the application's class name, the kernel build version,
 * and the list of loaded modules to populate the fallback template.
 */
#[DefaultEntrypoint("entrypoint")]
final class FallbackPage implements ControllerInterface
{
    public function entrypoint(): void
    {
        $this->sendTemplate("fallback", [
            "appClassname" => ObjectHelper::baseClassName($this->app::class),
            "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
            "modulesLoaded" => array_keys($this->app->context->domain)
        ]);
    }
}