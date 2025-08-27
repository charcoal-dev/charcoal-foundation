<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums\Http;

/**
 * This enum is used to specify the granularity of information to
 * be logged during HTTP operations. The levels define different
 * scopes of information ranging from no logging to complete
 * request and response data including headers and body.
 */
enum HttpLogLevel: int
{
    case None = 0;
    case Basic = 1;
    case Headers = 2;
    case Complete = 3;

    /**
     * Converts a string representation of a log level
     * into its corresponding HttpLogLevel instance.
     */
    public static function fromString(string $level): HttpLogLevel
    {
        $level = strtolower($level);
        return match ($level) {
            "none", "disabled", "false" => self::None,
            "basic" => self::Basic,
            "headers" => self::Headers,
            "complete", "full" => self::Complete,
            default => throw new \OutOfBoundsException("Invalid value for HttpLogLevel")
        };
    }
}