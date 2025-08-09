<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Policy\Concurrency;

use App\Shared\Core\Http\HttpInterfaceBinding;
use App\Shared\Exception\ConcurrentHttpRequestException;
use Charcoal\Filesystem\Directory;
use Charcoal\Semaphore\Exception\SemaphoreException;
use Charcoal\Semaphore\Filesystem\FileLock;
use Charcoal\Semaphore\FilesystemSemaphore;

/**
 * Class ConcurrencyEnforcer
 * @package App\Shared\Core\Http\Policy\Concurrency
 */
readonly class ConcurrencyEnforcer
{
    public function __construct(
        private ConcurrencyPolicy $policy,
        private string            $scopeLockId,
    )
    {
    }

    /**
     * @param HttpInterfaceBinding|null $interface
     * @param Directory $semaphoreDirectory
     * @param bool $autoRelease
     * @return FileLock
     * @throws ConcurrentHttpRequestException
     */
    public function acquireFileLock(
        ?HttpInterfaceBinding $interface,
        Directory             $semaphoreDirectory,
        bool                  $autoRelease = true
    ): FileLock
    {
        try {
            $httpSemaphore = new FilesystemSemaphore(
                $semaphoreDirectory->getDirectory($interface?->enum->value ?? "http", true));
        } catch (\Exception $e) {
            throw new \LogicException("Failed to initialize HTTP Semaphore", previous: $e);
        }

        try {
            $fileLock = $httpSemaphore->obtainLock($this->scopeLockId,
                $this->policy->maximumWaitTime > 0 ? $this->policy->tickInterval : null,
                max($this->policy->maximumWaitTime, 0),
            );

            if ($autoRelease) {
                $fileLock->setAutoRelease();
            }

            return $fileLock;
        } catch (SemaphoreException) {
            throw new ConcurrentHttpRequestException($this->policy->scope, $this->scopeLockId);
        }
    }
}