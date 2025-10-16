<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\SemaphoreProviderEnumInterface;
use Charcoal\App\Kernel\Enums\SemaphoreType;

/**
 * Represents the available semaphore providers.
 * This enum defines the supported semaphore backends and their associated configurations
 * and types.
 */
enum SemaphoreProviders: string implements SemaphoreProviderEnumInterface
{
    case Cache = "cache";
    case Local = "local";

    public function getConfigKey(): string
    {
        return $this->value;
    }

    public function getType(): SemaphoreType
    {
        return match ($this) {
            self::Cache => SemaphoreType::Redis,
            self::Local => SemaphoreType::LFS,
        };
    }
}