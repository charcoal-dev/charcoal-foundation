<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Enums\SemaphoreType;

/**
 * Enum representing types of semaphore nodes that can be used.
 */
enum SemaphoreNode
{
    case Local;
    case Shared;

    /**
     * Retrieves the type of semaphore being used.
     */
    public function getType(): SemaphoreType
    {
        return SemaphoreType::Filesystem;
    }
}