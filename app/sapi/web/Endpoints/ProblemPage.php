<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Web\Endpoints;

use Charcoal\Http\Server\Attributes\DefaultEntrypoint;
use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;
use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Class Problem
 * @package App\Sapi\Web\Endpoints
 * @api
 */
#[DefaultEntrypoint("entrypoint")]
final class ProblemPage implements ControllerInterface
{
    protected function entrypoint(): void
    {
        throw new \RuntimeException("This exception has been intentionally triggered so you can enjoy this attractive page",
            previous: new \LogicException("Nope! There doesn't seem to be anything wrong here."));
    }

    public function __invoke(RequestFacade $context): void
    {
        // TODO: Implement __invoke() method.
    }
}