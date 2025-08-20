<?php
declare(strict_types=1);

namespace App\Shared\Utility;


use Charcoal\Base\Charsets\Sanitizer\AsciiSanitizer;
use Charcoal\Base\Charsets\Sanitizer\Modifiers\ChangeCase;
use Charcoal\Base\Charsets\Sanitizer\Modifiers\CleanSpaces;
use Charcoal\Base\Charsets\Sanitizer\Modifiers\TrimStr;
use Charcoal\Base\Charsets\Sanitizer\Utf8Sanitizer;
use Charcoal\Base\Contracts\Charsets\UnicodeLanguageRangeInterface;

/**
 * Utility class for validating and processing contact information such as phone numbers,
 * email addresses, usernames, and names.
 */
final class ContactHelper
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
     * @api
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
     * @return AsciiSanitizer
     * @api
     */
    public static function getNameValidator(int $maxLength = 32): AsciiSanitizer
    {
        return (new AsciiSanitizer(true, true))
            ->modifiers(TrimStr::Both, CleanSpaces::All, ChangeCase::Titlecase)
            ->lengthRange(min: 2, max: $maxLength)
            ->matchRegEx('/^[a-z]+(\s[a-z]+)*$/i');
    }

    /**
     * @param int $maxLength
     * @param bool $allowDashes
     * @return AsciiSanitizer
     * @api
     */
    public static function getBrandNameValidator(int $maxLength = 32, bool $allowDashes = true): AsciiSanitizer
    {
        return (new AsciiSanitizer(true, true))
            ->modifiers(TrimStr::Both, CleanSpaces::All, ChangeCase::Titlecase)
            ->lengthRange(min: 2, max: $maxLength)
            ->matchRegEx($allowDashes ?
                '/^[a-z\-\_\.]+(\s[a-z0-9\-\_\.]+)*$/i' :
                '/^[a-z]+(\s[a-z0-9]+)*$/i'
            );
    }

    /**
     * @param int $maxLength
     * @param UnicodeLanguageRangeInterface ...$lang
     * @return Utf8Sanitizer
     * @api
     */
    public static function getNameValidatorUtf8(int $maxLength = 32, UnicodeLanguageRangeInterface ...$lang): Utf8Sanitizer
    {
        return (new Utf8Sanitizer(true, true, true))
            ->modifiers(TrimStr::Both, CleanSpaces::All, ChangeCase::Titlecase)
            ->lengthRange(min: 2, max: $maxLength)
            ->validateUnicodeRange(...$lang);
    }
}