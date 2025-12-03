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
        public int              $maxAttempts,
        public int              $duration = 3600,
        public bool             $status = true
    )
    {
        $this->action = is_string($action) ? new BruteForceAction($action) : $action;
        if ($this->maxAttempts < 1) {
            throw new \InvalidArgumentException("BFC max attempts must be greater than 0");
        }

        if ($this->duration < 1) {
            throw new \InvalidArgumentException("BFC duration must be greater than 0");
        }
    }
}