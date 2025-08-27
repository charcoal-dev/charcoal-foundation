<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData;

use App\Shared\Core\Orm\ModuleComponentEnum;

/**
 * Class CoreData
 * @package App\Shared\Foundation
 */
enum CoreData implements ModuleComponentEnum
{
    case BFC;
    case COUNTRIES;
    case DB_BACKUPS;
    case OBJECT_STORE;
    case SYSTEM_ALERTS;
}