<?php
declare(strict_types=1);

namespace App\Shared\Utility;

use Charcoal\App\Kernel\Support\NetworkHelper;

/**
 * Class NetworkValidator
 * @package App\Shared\Utility
 */
class NetworkHelper extends NetworkHelper
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