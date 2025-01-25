<?php
declare(strict_types=1);

namespace App\Shared\Core\Db;

/**
 * An enumeration representing databases embedded with app.
 */
enum Database: string
{
    case PRIMARY = "primary";
}