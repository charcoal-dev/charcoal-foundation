<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use Charcoal\Charsets\Support\AsciiHelper;

/**
 * Represents a policy for logging behavior, encapsulating whether logging
 * is enabled and an optional label for classification or identification.
 */
final readonly class LogPolicy
{
    public function __construct(
        public bool    $loggable,
        public ?string $label,
    )
    {
        if (strlen($this->label) > 80 || !AsciiHelper::isPrintableOnly($this->label)) {
            throw new \InvalidArgumentException("Invalid label: " . var_export($this->label, true));
        }
    }
}