<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Traits;

use App\Shared\Enums\SecretsStores;
use App\Shared\Enums\SemaphoreProviders;
use Charcoal\Security\Secrets\Enums\KeySize;

/**
 * Provide a trait for building security configurations.
 */
trait SecurityConfigBuilderTrait
{
    final protected function securityFromFileConfig(mixed $securityConfigData): void
    {
        if (!is_array($securityConfigData) || !$securityConfigData) {
            throw new \UnexpectedValueException("Invalid security configuration");
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

        foreach ($secretStores as $storeId => $storeConfig) {
            $storeEnum = SecretsStores::tryFrom(strval($storeId));
            if (!$storeEnum) {
                throw new \OutOfBoundsException("No matching secret store found between Enum and config ");
            }

            if (!is_array($storeConfig)) {
                throw new \InvalidArgumentException("Invalid secret store configuration");
            }

            if (!isset($storeConfig["path"]) || !is_string($storeConfig["path"]) || !$storeConfig["path"]) {
                throw new \InvalidArgumentException("Invalid secret store path configuration");
            }

            if (!isset($storeConfig["keySize"]) || !is_int($storeConfig["keySize"])) {
                throw new \InvalidArgumentException("Invalid secret store key size configuration");
            }

            $keySize = KeySize::tryFrom($storeConfig["keySize"]);
            if (!$keySize) {
                throw new \OutOfBoundsException("Invalid secret store key size configuration");
            }

            $this->security->declareSecretStore($storeEnum, $storeConfig["path"], $keySize);
        }

        unset($storeId, $storeEnum, $storeConfig, $keySize);
    }
}