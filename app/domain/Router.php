<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Domain;

use App\Sapi\Web\Endpoints\FallbackPage;
use App\Sapi\Web\Endpoints\ProblemPage;
use Charcoal\Http\Commons\Enums\HttpMethod;
use Charcoal\Http\Server\Routing\AppRoutes;
use Charcoal\Http\Server\Routing\Group\RouteGroupBuilder;

/**
 * Represents a readonly router that extends the functionality of the AppRoutes class.
 */
final readonly class Router extends AppRoutes
{
    final public function __construct()
    {
        parent::__construct(function (RouteGroupBuilder $group): void {
            $group->group("/", function (RouteGroupBuilder $group): void {
                $group->route("/", FallbackPage::class);
                $group->route("/problem", ProblemPage::class)->methods(HttpMethod::GET, HttpMethod::HEAD);
            });
        });
    }
}