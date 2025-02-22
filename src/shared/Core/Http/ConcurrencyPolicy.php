<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

/**
 * Class ConcurrencyPolicy
 * @package App\Shared\Core\Http
 */
enum ConcurrencyPolicy: int
{
    case NONE = 0;
    case IP_ADDR = 1;
    case AUTH_CONTEXT = 2;
}