<?php
declare(strict_types=1);

namespace App\Shared\Security\BruteForce;

/**
 * Interface BruteForceEnumInterface
 * @package App\Shared\Foundation\CoreData\BruteForceControl
 */
interface BruteForceEnumInterface extends \BackedEnum
{
    public function getPolicy(): BruteForcePolicy;
}