<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http;

use Charcoal\OOP\Traits\EnumOptionsTrait;

/**
 * Class HttpInterface
 * @package App\Shared\Foundation\Http
 */
enum HttpInterface: string
{
    case WEB = "web";

    use EnumOptionsTrait;
}