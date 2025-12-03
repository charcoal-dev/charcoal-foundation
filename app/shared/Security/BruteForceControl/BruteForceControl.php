<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Security\BruteForceControl;

use App\Shared\CoreData\Bfc\BfcRepository;
use App\Shared\Security\SecurityService;
use Charcoal\App\Kernel\Contracts\Security\SecurityModuleInterface;
use Charcoal\App\Kernel\Security\SecurityService as SecurityServiceKernel;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Base\Objects\Traits\ControlledSerializableTrait;

/**
 * Handles control mechanisms to prevent brute force attacks.
 */
final readonly class BruteForceControl implements SecurityModuleInterface
{
    use ControlledSerializableTrait;

    private BfcRepository $bfcIndex;

    /**
     * @param SecurityService $securityService
     * @return void
     */
    public function bootstrap(SecurityServiceKernel $securityService): void
    {
        $this->bfcIndex = $securityService->app->coreData->bfc;
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        return [];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
    }

    /**
     * @throws WrappedException
     */
    public function logEntry(
        BruteForcePolicy    $policy,
        ?\DateTimeImmutable $timestamp = null
    ): void
    {
        try {
            $this->bfcIndex->logEntry($policy->actor, $policy->action, $timestamp);
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to log BFC entry");
        }
    }

    /**
     * @throws WrappedException
     */
    public function getAttemptsCount(
        ?BruteForcePolicy   $policy,
        ?\DateTimeImmutable $timestamp = null
    ): int
    {
        try {
            return $this->bfcIndex->getCount($policy->actor, $policy->action, $policy->duration, $timestamp);
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to get BFC count");
        }
    }

    /**
     * @throws WrappedException
     */
    public function isBlocked(
        ?BruteForcePolicy   $policy,
        ?\DateTimeImmutable $timestamp = null
    ): bool
    {
        return $this->getAttemptsCount($policy, $timestamp) >= $policy->maxAttempts;
    }
}