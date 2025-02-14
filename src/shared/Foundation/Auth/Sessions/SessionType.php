<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Auth\Sessions;

use Charcoal\OOP\Traits\EnumOptionsTrait;

/**
 * Class SessionType
 * @package App\Shared\Foundation\Auth\Sessions
 */
enum SessionType: string
{
    case BROWSER = "browser";
    case APP = "app";

    use EnumOptionsTrait;
}