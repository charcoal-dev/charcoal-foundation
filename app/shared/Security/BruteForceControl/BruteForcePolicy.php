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
    public function __construct(
        public BruteForceAction $action,
        public int              $duration = 3600,
    )
    {
    }
}