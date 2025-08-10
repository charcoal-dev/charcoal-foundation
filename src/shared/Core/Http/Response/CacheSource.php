<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

/**
 * Class CacheSource
 * @package App\Shared\Core\Http\Response
 */
enum CacheSource
{
    case NONE;
    case CACHE;
    case FILESYSTEM;
}