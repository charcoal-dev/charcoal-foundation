<?php
declare(strict_types=1);

namespace App\Shared\Framework\CoreData;

use App\Shared\Core\Orm\AppOrmModule;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\Cipher\Cipher;

/**
 * Class CoreDataModule
 * @package App\Shared\Framework\CoreData
 */
class CoreDataModule extends AppOrmModule
{
    protected function declareChildren(AppBuildPartial $app): void
    {
    }

    protected function declareDatabaseTables(DatabaseTableRegistry $tables): void
    {
    }

    public function getCipher(AbstractOrmRepository $resolveFor): ?Cipher
    {
        return null;
    }
}