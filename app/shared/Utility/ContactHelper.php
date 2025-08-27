<?php
declare(strict_types=1);

namespace App\Shared\Utility;

use Charcoal\Charsets\SanitizerValidator;
use Charcoal\Charsets\SanitizerValidator\ASCII_Processor;
use Charcoal\Charsets\SanitizerValidator\UTF8_Processor;
use Charcoal\Charsets\Utf8Range;

/**
 * Class ContactHelper
 * @package App\Shared\Utility
 */
class ContactHelper
{
    /**
     * @param mixed $input
     * @param string $delimiter
     * @return bool
     */
    public static function isValidPhoneNumber(mixed $input, string $delimiter = "."): bool
    {
        if (is_string($input) && NumberHelper::inRange(strlen($input), 5, 20)) {
            if (preg_match('/^\+\d{1,6}(' . preg_quote($delimiter) . '\d{1,14})+$/', $input)) {
                return static::phoneToE164($input) !== false;
            }
        }

        return false;
    }

    /**
     * Get input value as E.164 format phone number
     * @param mixed $input
     * @return false|string
     */
    public static function phoneToE164(mixed $input): false|string
    {
        if (is_string($input)) {
            $input = "+" . preg_replace('/\D/', "", $input);
            if (preg_match('/^\+[1-9][0-9]{4,14}$/', $input)) {
                return $input;
            }
        }

        return false;
    }

    /**
     * @param string $input
     * @param bool $onlyAscii
     * @param int $maxLength
     * @return bool
     */
    public static function isValidEmailAddress(string $input, bool $onlyAscii = true, int $maxLength = 254): bool
    {
        if (NumberHelper::inRange(strlen($input), 6, $maxLength)) {
            if ($onlyAscii) {
                if (!preg_match('/^[\w@\-._+]+$/', $input)) {
                    return false;
                }
            }

            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
        }

        return false;
    }

    /**
     * @param mixed $input
     * @param int $maxLength
     * @return bool
     */
    public static function isValidUsername(mixed $input, int $maxLength = 20): bool
    {
        if (is_string($input) && NumberHelper::inRange(strlen($input), 4, $maxLength)) {
            return preg_match('/^[a-zA-Z0-9]+[a-zA-Z0-9\-_]?[a-zA-Z0-9]+$/', $input);
        }

        return false;
    }

    /**
     * @param int $maxLength
     * @return ASCII_Processor
     */
    public static function getNameValidator(int $maxLength = 32): ASCII_Processor
    {
        return SanitizerValidator::ASCII(true)
            ->trim()
            ->cleanSpaces()
            ->len(min: 3, max: $maxLength)
            ->match('/^[a-z]+(\s[a-z]+)*$/i')
            ->setCustomFn(function (string $validated) {
                return ucfirst($validated);
            });
    }

    /**
     * @param int $maxLength
     * @param bool $allowDashes
     * @return ASCII_Processor
     */
    public static function getBrandNameValidator(int $maxLength = 32, bool $allowDashes = true): ASCII_Processor
    {
        return SanitizerValidator::ASCII(true)
            ->trim()
            ->cleanSpaces()
            ->len(min: 3, max: $maxLength)
            ->match($allowDashes ? '/^[a-z\-\_\.]+(\s[a-z0-9\-\_\.]+)*$/i' : '/^[a-z]+(\s[a-z0-9]+)*$/i')
            ->setCustomFn(function (string $validated) {
                return ucfirst($validated);
            });
    }

    /**
     * @param int $maxLength
     * @return UTF8_Processor
     */
    public static function getNameValidatorUtf8(int $maxLength = 32): UTF8_Processor
    {
        return SanitizerValidator::UTF8(allowSpaces: true, filterInvalidChars: false)
            ->trim()
            ->cleanSpaces()
            ->len(min: 2, max: $maxLength)
            ->addCharset(Utf8Range::Arabic)
            ->addCharset(Utf8Range::Russian)
            ->addCharset(Utf8Range::Thai);
    }
}