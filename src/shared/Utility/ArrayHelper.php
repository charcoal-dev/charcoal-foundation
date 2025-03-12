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
     * @param array $data
     * @return array
     */
    public static function canonicalizeLexicographic(array $data): array
    {
        if (!static::isSequential($data)) {
            ksort($data, SORT_STRING);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::canonicalizeLexicographic($value);
            }
        }

        return $data;
    }

    /**
     * @param array $data
     * @return bool
     */
    public static function isSequential(array $data): bool
    {
        if (!$data) {
            return true;
        }

        return array_keys($data) === range(0, count($data) - 1);
    }

    /**
     * @param array|object $input
     * @return array
     * @throws \JsonException
     * @noinspection PhpMultipleClassDeclarationsInspection
     */
    public static function jsonFilter(array|object $input): array
    {
        return json_decode(json_encode($input), true, flags: JSON_THROW_ON_ERROR);
    }
}