<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\DbBackups;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;

/**
 * Class DbBackupsHandler
 * @package App\Shared\Foundation\CoreData\DbBackups
 * @property CoreDataModule $module
 */
class DbBackupsHandler extends AbstractOrmRepository
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::DB_BACKUPS);
    }
}