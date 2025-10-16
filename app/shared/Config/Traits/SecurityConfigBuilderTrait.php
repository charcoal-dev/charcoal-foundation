<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Traits;

use App\Shared\Enums\SecretsStores;
use App\Shared\Enums\SemaphoreProviders;

/**
 * Provide a trait for building security configurations.
 */
trait SecurityConfigBuilderTrait
{
    final protected function securityFromFileConfig(mixed $securityConfigData): void
    {
        if (!is_array($securityConfigData) || !$securityConfigData) {
            return;
        }

        // Semaphore providers
        $semaphoreProviders = $securityConfigData["semaphoreProviders"] ?? null;
        if (!is_array($semaphoreProviders) || !$semaphoreProviders) {
            throw new \InvalidArgumentException("Invalid semaphore providers configuration");
        }

        foreach ($semaphoreProviders as $providerId => $pathOrNode) {
            $providerEnum = SemaphoreProviders::tryFrom(strval($providerId));
            if (!$providerEnum) {
                throw new \OutOfBoundsException("No matching semaphore provider found between Enum and config ");
            }

            $this->security->declareSemaphore($providerEnum, strval($pathOrNode));
        }

        unset($providerId, $providerEnum, $pathOrNode);

        // Secret stores
        $secretStores = $securityConfigData["secretStores"] ?? null;
        if (!is_array($secretStores) || !$secretStores) {
            throw new \InvalidArgumentException("Invalid secret stores configuration");
        }

        foreach ($secretStores as $storeId => $pathOrNode) {
            $storeEnum = SecretsStores::tryFrom(strval($storeId));
            if (!$storeEnum) {
                throw new \OutOfBoundsException("No matching secret store found between Enum and config ");
            }

            $this->security->declareSecretStore($storeEnum, strval($pathOrNode));
        }

        unset($storeId, $storeEnum, $pathOrNode);
    }
}