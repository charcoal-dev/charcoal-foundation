<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Sapi\Http;

use App\Sapi\Web\Endpoints\HomePage;
use App\Sapi\Web\Endpoints\ProblemPage;
use App\Shared\Sapi\Http\Middleware\GlobalPipelines;
use Charcoal\App\Kernel\ServerApi\Http\AppRouter;
use Charcoal\Http\Server\Middleware\MiddlewareRegistry;
use Charcoal\Http\Server\Routing\Group\RouteGroupBuilder;
use Charcoal\Http\Server\Routing\HttpRoutes;

/**
 * Represents a readonly router that extends the functionality of the AppRoutes class.
 */
final readonly class WebRouter extends AppRouter
{
    /**
     * @throws \Charcoal\Http\Server\Exceptions\RoutingBuilderException
     */
    protected function declareRoutes(): HttpRoutes
    {
        return new HttpRoutes(function (RouteGroupBuilder $group): void {
            $group->route("/", HomePage::class);
            $group->route("/problem", ProblemPage::class);
        });
    }

    protected function middleware(): MiddlewareRegistry
    {
        return GlobalPipelines::getInstance();
    }

    public function onServerConstruct(MiddlewareRegistry $mw): void
    {
    }
}