<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\DbBackups;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;

/**
 * Service class for handling database backup operations by using the ORM repository base functionality.
 * @property CoreDataModule $module
 */
final class DbBackupService extends OrmRepositoryBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::DatabaseBackups);
    }
}