<?php
declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Class TypeCaster
 * @package App\Shared\Utility
 */
class TypeCaster
{
    /**
     * @param mixed $input
     * @return bool
     */
    public static function toBool(mixed $input): bool
    {
        if (is_bool($input)) {
            return $input;
        }

        if ($input === 1) {
            return true;
        }

        if (is_string($input) && in_array(strtolower($input), ["1", "true", "on", "yes"])) {
            return true;
        }

        return false;
    }
}