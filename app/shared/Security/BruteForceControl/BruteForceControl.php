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
     * @throws \Charcoal\Database\Exceptions\QueryExecuteException
     */
    public function logEntry(
        BruteForcePolicy    $policy,
        BruteForceActor     $actor,
        ?\DateTimeImmutable $timestamp = null
    ): void
    {
        $this->bfcIndex->logEntry($actor, $policy->action, $timestamp);
    }

    /**
     * @throws \Charcoal\Database\Exceptions\QueryExecuteException
     * @throws \Charcoal\Database\Exceptions\QueryFetchException
     */
    public function getCount(
        ?BruteForcePolicy   $policy,
        ?BruteForceActor    $actor = null,
        ?\DateTimeImmutable $timestamp = null
    ): int
    {
        return $this->bfcIndex->getCount($actor, $policy->action, $policy->duration, $timestamp);
    }
}