<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Traits;

use App\Shared\Enums\Databases;
use App\Shared\Enums\SecretsStores;
use Charcoal\App\Kernel\Config\Snapshot\DatabaseConfig;
use Charcoal\App\Kernel\Security\Secrets\SecretRef;
use Charcoal\Database\Enums\DbConnectionStrategy;
use Charcoal\Database\Enums\DbDriver;

/**
 * Provides a method to build and configure database connection objects from configuration data.
 */
trait DatabaseConfigBuilderTrait
{
    final protected function databasesFromFileConfig(mixed $dbConfigData): void
    {
        if (!is_array($dbConfigData) || !$dbConfigData) {
            return;
        }

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

            // Resolve password value
            $password = $dbConfig["password"] ?? null;
            if (is_array($password)) {
                try {
                    $password = new SecretRef(
                        SecretsStores::from($password["store"]),
                        $password["ref"] ?? null,
                        $password["version"] ?? -1,
                        $password["namespace"] ?? 1
                    );
                } catch (\Throwable $t) {
                    throw new \InvalidArgumentException("Invalid secret reference for DB: " . $key->name,
                        previous: $t);
                }
            }

            // Append Configuration
            $this->database->set($key, new DatabaseConfig(
                $driver,
                $dbConfig["database"] ?? "",
                $dbConfig["host"] ?? "localhost",
                $dbConfig["port"] ?? null,
                $dbConfig["username"] ?? null,
                $password,
                $connection
            ));
        }
    }
}