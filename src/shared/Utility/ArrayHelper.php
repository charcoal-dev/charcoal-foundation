<?php
declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Class ArrayHelper
 * @package App\Shared\Utility
 */
class ArrayHelper
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
     * @param array|object $input
     * @return array
     * @throws \JsonException
     */
    public static function jsonFilter(array|object $input): array
    {
        return json_decode(json_encode($input), true, flags: JSON_THROW_ON_ERROR);
    }
}