<?php
declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Class NumberHelper
 * @package App\Shared\Utility
 */
final class NumberHelper
{
    /**
     * @param mixed $input
     * @param int $min
     * @param int $max
     * @return bool
     */
    public static function inRange(mixed $input, int $min, int $max): bool
    {
        if (!is_int($input)) {
            return false;
        }

        return $input >= $min && $input <= $max;
    }

    /**
     * @param string $value
     * @return string
     * @api
     */
    public static function cleanFloatString(string $value): string
    {
        return strpos($value, ".") > 0 ? rtrim(rtrim($value, "0"), ".") : $value;
    }
}