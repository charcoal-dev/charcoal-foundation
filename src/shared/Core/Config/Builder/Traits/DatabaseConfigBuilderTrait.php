<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use App\Shared\Enums\Databases;
use Charcoal\App\Kernel\Config\Builder\DbConfigObjectsBuilder;
use Charcoal\App\Kernel\Config\Snapshot\DatabaseConfig;
use Charcoal\Database\Enums\DbConnectionStrategy;
use Charcoal\Database\Enums\DbDriver;

/**
 * Provides a method to build and configure database connection objects from configuration data.
 */
trait DatabaseConfigBuilderTrait
{
    final protected function getDatabasesConfig(array|null $dbConfigData): DbConfigObjectsBuilder
    {
        $dbConfigs = new DbConfigObjectsBuilder();
        if (is_array($dbConfigData)) {
            foreach ($dbConfigData as $dbId => $dbConfig) {
                $key = Databases::tryFrom(strval($dbId));
                if (!$dbId) {
                    throw new \OutOfBoundsException("No matching database found between Enum and config ");
                }

                $driver = DbDriver::tryFrom(strval($dbConfig["driver"]));
                if (!$driver) {
                    throw new \OutOfBoundsException("Invalid database driver for: " . $key->name);
                }

                $connection = match (strtolower(strval($dbConfig["connection"]))) {
                    "lazy", "ondemand" => DbConnectionStrategy::Lazy,
                    "default", "normal", "" => DbConnectionStrategy::Normal,
                    default => throw new \UnexpectedValueException(
                        "Invalid database connection strategy for: " . $key->name
                    ),
                };

                // Append Configuration
                $dbConfigs->set($key, new DatabaseConfig(
                    $driver,
                    $dbConfig["database"] ?? "",
                    $dbConfig["host"] ?? "localhost",
                    $dbConfig["port"] ?? null,
                    $dbConfig["username"] ?? null,
                    $dbConfig["password"] ?? null,
                    $connection
                ));
            }
        }

        return $dbConfigs;
    }
}