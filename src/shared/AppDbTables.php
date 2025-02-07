<?php
declare(strict_types=1);

namespace App\Shared;

use Charcoal\App\Kernel\Orm\Db\DatabaseEnum;
use Charcoal\App\Kernel\Orm\Db\DbAwareTableEnum;

/**
 * Class AppDbTables
 * @package App\Shared
 */
enum AppDbTables: string implements DbAwareTableEnum
{
    // CoreData Module:
    case BFC = "bfc_table";
    case COUNTRIES = "countries";
    case DB_BACKUPS = "db_backups";
    case OBJECT_STORE = "object_store";
    case SYSTEM_ALERTS = "sys_alerts";

    public function getTableName(): string
    {
        return $this->value;
    }

    public function getDatabase(): DatabaseEnum
    {

    }
}