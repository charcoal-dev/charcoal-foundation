<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Security\BruteForceControl;

/**
 * Represents a policy for handling brute force attempts.
 */
final readonly class BruteForcePolicy
{
    public BruteForceAction $action;

    public function __construct(
        public BruteForceActor  $actor,
        BruteForceAction|string $action,
        public int              $duration = 3600,
    )
    {
        $this->action = is_string($action) ? new BruteForceAction($action) : $action;
        if ($this->duration < 1) {
            throw new \InvalidArgumentException("BFC duration must be greater than 0");
        }
    }
}