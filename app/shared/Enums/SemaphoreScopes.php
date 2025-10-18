<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\SemaphoreProviderEnumInterface;
use Charcoal\App\Kernel\Contracts\Enums\SemaphoreScopeEnumInterface;
use Charcoal\Base\Enums\Traits\EnumFindCaseTrait;

/**
 * Enumeration representing different semaphore scope types.
 * Implements SemaphoreScopeEnumInterface for unified behavior across scopes.
 */
enum SemaphoreScopes: string implements SemaphoreScopeEnumInterface
{
    use EnumFindCaseTrait;

    case Orm = "orm";
    case Http = "http";
    case Cli = "cli";

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->value;
    }

    /**
     * @return SemaphoreProviderEnumInterface
     */
    public function provider(): SemaphoreProviderEnumInterface
    {
        return match ($this) {
            self::Orm => SemaphoreProviders::Local,
            default => SemaphoreProviders::Cache,
        };
    }
}