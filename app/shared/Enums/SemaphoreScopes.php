<?php
declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\SemaphoreScopeEnumInterface;

/**
 * Class SemaphoreScopes
 * @package App\Shared\Enums
 */
enum SemaphoreScopes: string implements SemaphoreScopeEnumInterface
{
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
}