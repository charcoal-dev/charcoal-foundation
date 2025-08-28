<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use App\Shared\Core\Config\Persisted\HttpInterfaceConfig;
use App\Shared\Enums\Http\HttpInterface;
use App\Shared\Enums\Http\HttpLogLevel;

/**
 * Provides logic for building and configuring HTTP interface configurations.
 * Designed to validate and construct instances of HttpInterfaceConfig
 * based on an array of interface definitions.
 * @deprecated
 */
trait HttpFileConfigTrait
{
    /**
     * @param array $configData
     * @return void
     * @api
     */
    protected function httpInterfacesFromFileConfig(mixed $configData): void
    {
        if (!is_array($configData) || !$configData) {
            return;
        }

        /** @var array<HttpInterface, HttpInterfaceConfig> $ifConfig */
        foreach ($this->getHttpInterfacesConfig($configData["interfaces"] ?? null) as $ifConfig) {
            $this->http->set($ifConfig[0], $ifConfig[1]);
        }
    }

    /**
     * @return array<array<, HttpInterfaceConfig>>
     * @api
     */
    protected function getHttpInterfacesConfig(mixed $interfaces): array
    {
        if (!is_array($interfaces) || !$interfaces) {
            return [];
        }

        $result = [];
        foreach ($interfaces as $ifId => $ifConfig) {
            $ifId = HttpInterface::tryFrom(strval($ifId));
            if (!$ifId instanceof HttpInterface) {
                throw new \DomainException("No such HTTP interface declared in " . HttpInterface::class);
            }

            if (!is_array($ifConfig)) {
                throw new \UnexpectedValueException("Bad HTTP interface configuration for: " . $ifId->name);
            }

            $status = $ifConfig["status"] ?? null;
            if (!is_bool($status)) {
                throw new \UnexpectedValueException('Invalid HTTP interface "status" config for: ' . $ifId->name);
            }

            $logData = HttpLogLevel::fromString(strval($ifConfig["logData"]));
            $logHttpMethodOptions = $ifConfig["logHttpMethodOptions"] ?? null;
            if (!is_bool($logHttpMethodOptions)) {
                throw new \UnexpectedValueException('Invalid HTTP interface "logHttpMethodOptions" config for: '
                    . $ifId->name);
            }

            $ifConfig = new HttpInterfaceConfig();
            $ifConfig->status = $status;
            $ifConfig->logData = $logData;
            $ifConfig->logHttpMethodOptions = $logHttpMethodOptions;
            $ifConfig->traceHeader = is_string($ifConfig["traceHeader"]) ? $ifConfig["traceHeader"] : null;
            $ifConfig->cachedResponseHeader = is_string($ifConfig["cachedResponseHeader"]) ?
                $ifConfig["cachedResponseHeader"] : null;
            $result[] = [$ifId, $ifConfig];
        }

        return $result;
    }
}