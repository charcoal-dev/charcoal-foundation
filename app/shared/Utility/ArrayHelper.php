<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Provides utility methods for working with arrays.
 */
final readonly class ArrayHelper extends \Charcoal\Base\Support\Helpers\ArrayHelper
{
    /**
     * @param array $data
     * @param array $excludedKeys
     * @return array
     */
    public static function excludeKeys(array $data, array $excludedKeys = []): array
    {
        if (!$excludedKeys) {
            return $data;
        }

        $excludedKeysLc = array_map("strtolower", $excludedKeys);
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $excludedKeysLc, true)) {
                unset($data[$key]);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @return string
     * @api
     */
    public static function canonicalizeLexicographicJson(array $data): string
    {
        return json_encode(self::canonicalizeLexicographic($data),
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param array|object $input
     * @return array
     * @throws \JsonException
     * @api
     */
    public static function jsonFilter(array|object $input): array
    {
        return json_decode(json_encode($input), true, flags: JSON_THROW_ON_ERROR);
    }
}