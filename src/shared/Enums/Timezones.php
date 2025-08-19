<?php
declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\TimezoneEnumInterface;

/**
 * Class Timezones
 * @package App\Shared\Enums
 */
enum Timezones: string implements TimezoneEnumInterface
{
    case UTC = "UTC";
    case EUROPE_LONDON = "Europe/London";
    case ASIA_DUBAI = "Asia/Dubai";
    case ASIA_ISLAMABAD = "Asia/Karachi";

    public function getTimezoneId(): string
    {
        return $this->value;
    }
}