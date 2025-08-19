<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use App\Shared\Enums\CacheStores;
use Charcoal\App\Kernel\Config\Builder\CacheConfigObjectsBuilder;
use Charcoal\App\Kernel\Config\Snapshot\CacheStoreConfig;
use Charcoal\App\Kernel\Enums\CacheDriver;

/**
 * Provides functionality to construct and configure cache settings using data from an
 * input configuration array. This trait is intended to be used by classes that manage
 * cache configurations.
 */
trait CacheConfigBuilderTrait
{
    final protected function getCacheConfig(mixed $configData): CacheConfigObjectsBuilder
    {
        $cacheConfig = new CacheConfigObjectsBuilder();
        if (!is_array($configData) || !$configData) {
            return $cacheConfig;
        }

        $cacheStores = $configData["stores"] ?? null;
        if (!is_array($cacheStores) || !$cacheStores) {
            return $cacheConfig;
        }

        foreach ($cacheStores as $storeId => $cacheServer) {
            $storeId = CacheStores::tryFrom(strval($storeId));
            if (!$storeId) {
                throw new \OutOfBoundsException("No matching cache store found between Enum and config ");
            }

            // Cache Driver
            $driver = CacheDriver::tryFrom(strval($cacheServer["driver"]));
            if (!$driver) {
                throw new \OutOfBoundsException("Invalid cache driver in configuration");
            }

            $host = $cacheServer["host"] ?? "";
            $port = $cacheServer["port"] ?? 0;
            $timeout = $cacheServer["timeout"] ?? 0;
            $cacheConfig->set($storeId, new CacheStoreConfig($driver, $host, $port, $timeout));
        }

        return $cacheConfig;
    }
}