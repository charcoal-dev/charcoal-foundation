<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Represents different levels of HTTP call logging.
 */
enum HttpCallLog
{
    case None;
    case Basic;
    case Headers;
    case Complete;

    /**
     * @param string $level
     * @return self
     */
    public static function fromString(string $level): self
    {
        $level = strtolower($level);
        return match ($level) {
            "none", "disabled", "false" => self::None,
            "basic" => self::Basic,
            "headers" => self::Headers,
            "complete", "full" => self::Complete,
            default => throw new \OutOfBoundsException("Invalid value for HttpCallLog")
        };
    }
}