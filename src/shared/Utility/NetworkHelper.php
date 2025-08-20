<?php
declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Provides utility methods to work with HTTP network operations.
 */
final class NetworkHelper extends \Charcoal\App\Kernel\Support\NetworkHelper
{
    /**
     * @param mixed $origin
     * @return bool
     */
    public static function isValidHttpOrigin(mixed $origin): bool
    {
        if (!is_string($origin) || trim($origin) === "") {
            return false;
        }

        if (preg_match('/^(https?):\/\/([a-zA-Z0-9.-]+)(:\d+)?$/', $origin)) {
            return true;
        }

        return false;
    }
}