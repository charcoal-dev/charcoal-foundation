<?php
declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Class StringHelper
 * @package App\Shared\Utility
 */
final class StringHelper
{
    /**
     * @param mixed $input
     * @return string|null
     */
    public static function getTrimmedOrNull(mixed $input): ?string
    {
        if (is_string($input)) {
            $input = trim($input);
            if (!$input) {
                return null;
            }

            return $input;
        }

        return null;
    }
}