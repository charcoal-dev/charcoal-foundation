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
 * The HomePage class handles the rendering of a fallback template
 * with contextual information about the application.
 */
#[DefaultEntrypoint("entrypoint")]
final class HomePage implements ControllerInterface
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