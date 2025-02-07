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
    case OBJECT_STORE;
    case COUNTRIES;
    case DB_BACKUPS;
    case SYSTEM_ALERTS;
    case BFC;
}