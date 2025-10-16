<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\SecretsStoreEnumInterface;
use Charcoal\App\Kernel\Enums\SecretsStoreType;

/**
 * Represents the enumeration of secret store types.
 * Implements the SecretsStoreEnumInterface for defining standardized behavior across secret stores.
 */
enum SecretsStores: string implements SecretsStoreEnumInterface
{
    case Local = "local";

    public function getConfigKey(): string
    {
        return $this->value;
    }

    public function getStoreType(): SecretsStoreType
    {
        return SecretsStoreType::LFS;
    }
}