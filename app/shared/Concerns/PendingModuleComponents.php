<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Concerns;

use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Cipher\Cipher;
use Charcoal\Semaphore\Contracts\SemaphoreProviderInterface;

/**
 * Todo: Pending Implementation
 */
trait PendingModuleComponents
{
    /**
     * @throws \RuntimeException
     */
    public function getCipherFor(OrmRepositoryBase $resolveFor): ?Cipher
    {
        throw new \RuntimeException("Not implemented");
    }

    /**
     * @throws \RuntimeException
     */
    public function getSemaphore(): SemaphoreProviderInterface
    {
        throw new \RuntimeException("Not implemented");
    }
}