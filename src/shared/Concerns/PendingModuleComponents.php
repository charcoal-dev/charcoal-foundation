<?php
declare(strict_types=1);

namespace App\Shared\Concerns;

use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Cipher\Cipher;
use Charcoal\Semaphore\Filesystem\FilesystemSemaphore;

/**
 * Todo: Pending Implementation
 */
trait PendingModuleComponents
{
    /**
     * @throws \Exception
     */
    public function getCipherFor(OrmRepositoryBase $resolveFor): ?Cipher
    {
        throw new \Exception("Not implemented");
    }

    /**
     * @throws \Exception
     */
    public function getSemaphore(): FilesystemSemaphore
    {
        throw new \Exception("Not implemented");
    }
}