<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Security\BruteForceControl;

/**
 * Represents an immutable action that requires a valid string identifier.
 * This class ensures the provided string adheres to a specific pattern
 * and length, throwing an exception if the conditions are not met.
 */
final readonly class BruteForceAction
{
    public function __construct(public string $value)
    {
        if (!preg_match('/\A\w{6,64}\z/i', $this->value)) {
            throw new \InvalidArgumentException("Bad action string for BruteForceAction");
        }
    }
}