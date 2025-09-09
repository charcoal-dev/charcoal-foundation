<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Web\Endpoints;

use App\Sapi\Web\Core\WebTemplatesTrait;
use App\Shared\CharcoalApp;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Http\Commons\Enums\CacheControl;
use Charcoal\Http\Commons\Support\CacheControlDirectives;
use Charcoal\Http\Server\Attributes\CacheControlAttribute;
use Charcoal\Http\Server\Attributes\DefaultEntrypoint;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;
use Composer\InstalledVersions;

/**
 * The HomePage class handles the rendering of a fallback template
 * with contextual information about the application.
 */
#[DefaultEntrypoint("entrypoint")]
#[CacheControlAttribute(new CacheControlDirectives(CacheControl::Private, maxAge: 3600, mustRevalidate: false))]
final class HomePage implements ControllerInterface
{
    use WebTemplatesTrait;

    /**
     * @param GatewayFacade $request
     * @return never
     * @throws \Charcoal\Http\Server\Exceptions\Internal\Response\BypassEncodingInterrupt
     */
    public function entrypoint(GatewayFacade $request): never
    {
        var_dump(json_encode($request->request()->headers));
        var_dump(json_encode($request->server()->proxy->proxy));
        $this->sendTemplate(
            $request,
            "home",
            [
                "clientIpAddress" => $request->server()->proxy->clientIp,
                "appClassname" => ObjectHelper::baseClassName(CharcoalApp::getAppFqcn()),
                "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
                "modulesLoaded" => []
            ]
        );
    }
}