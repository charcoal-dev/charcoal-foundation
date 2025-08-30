<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Web\Endpoints;

use Charcoal\Http\Server\Contracts\Controllers\ControllerInterface;

/**
 * Class Problem
 * @package App\Sapi\Web\Endpoints
 * @api
 */
class ProblemPage implements ControllerInterface
{
    protected function entrypoint(): void
    {
        throw new \RuntimeException("This exception has been intentionally triggered so you can enjoy this attractive page",
            previous: new \LogicException("Nope! There doesn't seem to be anything wrong here."));
    }
}