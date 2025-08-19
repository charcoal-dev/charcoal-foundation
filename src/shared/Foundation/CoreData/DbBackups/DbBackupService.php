<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\DbBackups;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;

/**
 * Class DbBackupsHandler
 * @package App\Shared\Foundation\CoreData\DbBackups
 * @property CoreDataModule $module
 */
class DbBackupService extends OrmRepositoryBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::DatabaseBackups);
    }
}