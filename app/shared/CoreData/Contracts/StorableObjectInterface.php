<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Contracts;

/**
 * Marker for objects that can be stored in CoreData's objectStore module.
 */
interface StorableObjectInterface
{
    public static function getStorageRef(): string;

    public static function getCurrentVersion(): int;

    public function overrideCacheTtl(): ?int;
}