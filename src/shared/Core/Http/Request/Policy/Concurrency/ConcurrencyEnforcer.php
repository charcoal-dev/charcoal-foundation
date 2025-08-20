<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Policy\Concurrency;

use App\Shared\Exceptions\Http\ConcurrentHttpRequestException;
use Charcoal\App\Kernel\Contracts\Enums\SemaphoreScopeEnumInterface;
use Charcoal\App\Kernel\Security\SemaphoreService;
use Charcoal\Semaphore\Exceptions\SemaphoreException;
use Charcoal\Semaphore\Filesystem\FileLock;

/**
 * Class ConcurrencyEnforcer
 * @package App\Shared\Core\Http\Policy\Concurrency
 */
readonly class ConcurrencyEnforcer
{
    public function __construct(
        private ConcurrencyPolicy           $policy,
        private SemaphoreScopeEnumInterface $scope,
        private string                      $scopeLockId,
    )
    {
    }

    /**
     * @param SemaphoreService $semaphoreService
     * @param bool $autoRelease
     * @return FileLock
     * @throws ConcurrentHttpRequestException
     */
    public function acquireFileLock(
        SemaphoreService $semaphoreService,
        bool             $autoRelease = true
    ): FileLock
    {

        try {
            $fileLock = $semaphoreService->lock($this->scope, $this->scopeLockId,
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