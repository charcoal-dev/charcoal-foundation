<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Exceptions;

/**
 * Represents an exception thrown when the system is in maintenance mode.
 */
final class MaintenanceException extends \Exception
{
    public function __construct(string $message = "Maintenance mode is enabled")
    {
        parent::__construct($message);
    }
}