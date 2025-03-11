<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Cors;

/**
 * Class CorsPolicy
 * @package App\Shared\Core\Http\Cors
 */
enum CorsPolicy
{
    case DISABLED;
    case ALLOW_ALL;
    case ENFORCE_ORIGIN;
}