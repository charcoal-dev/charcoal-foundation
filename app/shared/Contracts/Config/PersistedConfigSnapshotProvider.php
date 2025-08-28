<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Contracts\Config;

use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;

/**
 * Represents a source of persisted configuration that provides the ability
 * to generate a snapshot of its current state.
 */
interface PersistedConfigSnapshotProvider
{
    public function snapshot(): ConfigSnapshotInterface;
}