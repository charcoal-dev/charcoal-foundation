<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http;

/**
 * Class LogLevel
 * @package App\Shared\Foundation\Http
 */
enum HttpLogLevel: int
{
    case NONE = 0;
    case BASIC = 1;
    case HEADERS = 2;
    case COMPLETE = 3;

    /**
     * @param string $level
     * @return HttpLogLevel
     */
    public static function fromString(string $level): HttpLogLevel
    {
        $level = strtolower($level);
        return match ($level) {
            "none", "disabled", "false" => self::NONE,
            "basic" => self::BASIC,
            "headers" => self::HEADERS,
            "complete", "full" => self::COMPLETE,
            default => throw new \OutOfBoundsException("Invalid value for HttpLogLevel")
        };
    }
}