<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Security\BruteForceControl;

use Charcoal\Http\Server\Request\Controller\RequestFacade;

/**
 * Represents an actor involved in a brute force attempt.
 * This class is designed to encapsulate details of the actor, such as its identification.
 * It provides a factory method for creation based on a request's IP address.
 */
final readonly class BruteForceActor
{
    /**
     * @param RequestFacade $request
     * @return self
     */
    public static function fromIpAddress(RequestFacade $request): self
    {
        return new self($request->clientIp);
    }

    /**
     * @param string $value
     */
    private function __construct(public string $value)
    {
    }
}