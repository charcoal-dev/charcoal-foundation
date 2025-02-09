<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http;

use App\Shared\Core\Orm\ModuleComponentEnum;

/**
 * Class Http
 * @package App\Shared\Foundation\Http
 */
enum Http implements ModuleComponentEnum
{
    case REQUEST_LOG;
    case CALL_LOG;
    case PROXY_SERVERS;
}