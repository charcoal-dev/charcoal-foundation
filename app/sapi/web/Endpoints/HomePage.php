<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Web\Endpoints;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\Html\RenderHtmlTemplateTrait;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Http\Server\Attributes\DefaultEntrypoint;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;
use Composer\InstalledVersions;

/**
 * The HomePage class handles the rendering of a fallback template
 * with contextual information about the application.
 */
#[DefaultEntrypoint("entrypoint")]
final class HomePage implements ControllerInterface
{
    use RenderHtmlTemplateTrait;

    public function entrypoint(GatewayFacade $request): void
    {

        var_dump($request->request());
//        $this->sendTemplate("fallback", [
//            "appClassname" => ObjectHelper::baseClassName(CharcoalApp::getAppFqcn()),
//            "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
//            "modulesLoaded" => array_keys($this->app->context->domain)
//        ]);
    }
}