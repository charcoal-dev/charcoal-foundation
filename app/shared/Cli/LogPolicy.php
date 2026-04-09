<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

/**
 * Represents a policy for logging behavior, encapsulating whether logging
 * is enabled and an optional label for classification or identification.
 */
final readonly class LogPolicy
{
    public function __construct(
        public bool    $status,
        public ?string $label,
        public bool    $captureStateChanges = false
    )
    {
    }
}