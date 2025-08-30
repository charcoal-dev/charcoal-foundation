<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Domain;

use App\Sapi\Web\Endpoints\FallbackPage;
use App\Sapi\Web\Endpoints\ProblemPage;
use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Contracts\EntryPoint\AppRoutesProviderInterface;
use Charcoal\App\Kernel\Contracts\Enums\SapiEnumInterface;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Server\Middleware\MiddlewareRegistry;
use Charcoal\Http\Server\Routing\AppRoutes;
use Charcoal\Http\Server\Routing\Group\RouteGroupBuilder;

/**
 * Represents a readonly router that extends the functionality of the AppRoutes class.
 */
final readonly class WebRoutes extends AppRoutes implements AppRoutesProviderInterface
{
    public Interfaces $sapi;

    final public function __construct()
    {
        $this->sapi = Interfaces::Web;
        parent::__construct(function (RouteGroupBuilder $group): void {
            $group->route("/", FallbackPage::class);
            $group->route("/problem", ProblemPage::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
        });
    }

    /**
     * Configures the pipeline callback using the provided middleware registry.
     */
    public function configPipelineCallback(MiddlewareRegistry $mw): void
    {
    }

    /**
     * Retrieves the current SAPI (Server API) instance.
     */
    public function sapi(): SapiEnumInterface
    {
        return $this->sapi;
    }

    /**
     * @return AppRoutes
     */
    public function routes(): AppRoutes
    {
        return $this;
    }
}