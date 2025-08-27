<?php
declare(strict_types=1);

namespace App\Shared\Core\Orm;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Orm\AbstractOrmModule;
use Charcoal\Semaphore\FilesystemSemaphore;

/**
 * Class AppOrmModule
 * @package App\Shared\Core\Orm
 * @property CharcoalApp $app
 */
abstract class AppOrmModule extends AbstractOrmModule
{
    protected ?FilesystemSemaphore $ormSemaphore = null;

    /**
     * @return FilesystemSemaphore
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Semaphore\Exception\SemaphoreException
     */
    public function getSemaphore(): FilesystemSemaphore
    {
        if (!$this->ormSemaphore) {
            $this->ormSemaphore = new FilesystemSemaphore(
                $this->app->directories->semaphore->getDirectory("orm", true));
        }

        return $this->ormSemaphore;
    }
}