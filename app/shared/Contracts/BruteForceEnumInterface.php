<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Contracts;

use App\Shared\Security\BruteForce\BruteForcePolicy;

/**
 * Interface BruteForceEnumInterface
 * @package App\Shared\Foundation\CoreData\BruteForceControl
 */
interface BruteForceEnumInterface extends \BackedEnum
{
    public function getPolicy(): BruteForcePolicy;
}