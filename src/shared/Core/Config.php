<?php
declare(strict_types=1);

namespace App\Shared\Core;

use App\Shared\Utility\NetworkValidator;
use Charcoal\App\Kernel\Config\CacheConfig;
use Charcoal\App\Kernel\Config\CacheDriver;
use Charcoal\App\Kernel\Config\DbConfigs;
use Charcoal\App\Kernel\DateTime\Timezone;
use Charcoal\Database\DbCredentials;
use Charcoal\Database\DbDriver;
use Charcoal\Filesystem\Directory;
use Charcoal\Yaml\Parser;

/**
 * Class Config
 * @package App\Shared\Core
 */
class Config extends \Charcoal\App\Kernel\Config
{
    /**
     * @param Directory $configDir
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function __construct(Directory $configDir)
    {
        $configData = (new Parser(evaluateBooleans: true, evaluateNulls: true))
            ->getParsed($configDir->pathToChild("/config.yml", false));

        parent::__construct(
            Timezone::from(strval($configData["timezone"])),
            $this->getCacheConfig($configData["core"]["cache"] ?? null),
            $this->getDatabasesConfig($configData["core"]["databases"] ?? null),
        );
    }

    /**
     * @return string|null
     */
    protected function resolveMySqlRootPassword(): ?string
    {
        $mysqlRootPassword = trim(strval(getenv("MYSQL_ROOT_PASSWORD")));
        return empty($mysqlRootPassword) ? null : $mysqlRootPassword;
    }

    /**
     * @param array|null $cacheConfig
     * @return CacheConfig
     */
    private function getCacheConfig(array|null $cacheConfig): CacheConfig
    {
        if (!is_array($cacheConfig)) {
            return new CacheConfig(CacheDriver::NULL, "0.0.0.0", 0, 0);
        }

        // Cache Driver
        $driver = CacheDriver::tryFrom(strval($cacheConfig["driver"]));
        if (!$driver) {
            throw new \OutOfBoundsException("Invalid cache driver in configuration");
        }

        if ($driver === CacheDriver::NULL) {
            return new CacheConfig(CacheDriver::NULL, "0.0.0.0", 0, 0);
        }

        // Validations on host, port and timeout
        if (!NetworkValidator::isValidIpAddress($cacheConfig["host"] ?? null, ipv4: true, ipv6: false)) {
            throw new \DomainException("Configured cache host is not valid IPv4 address");
        }

        $port = $cacheConfig["port"] ?? null;
        if (!is_int($port) || $port < 1000 || $port > 65535) {
            throw new \InvalidArgumentException("Invalid configured cache port value");
        }

        $timeout = $cacheConfig["timeout"] ?? null;
        if (!is_int($timeout) || $timeout < 1 || $timeout > 6) {
            throw new \OutOfRangeException("Invalid configured cache timeout value");
        }

        // Return new CacheConfig
        return new CacheConfig($driver, $cacheConfig["host"], $port, $timeout);
    }

    /**
     * @param array|null $dbConfigData
     * @return DbConfigs
     */
    private function getDatabasesConfig(array|null $dbConfigData): DbConfigs
    {
        $dbConfigs = new DbConfigs(mysqlRootPassword: $this->resolveMySqlRootPassword());
        if (is_array($dbConfigData)) {
            foreach ($dbConfigData as $dbId => $dbConfig) {
                // Database Instance Key
                if (!is_string($dbId) || !preg_match('/^\w{2,20}$/', $dbId)) {
                    throw new \InvalidArgumentException('Invalid label for database object in YML');
                }

                // Database Driver
                $driver = DbDriver::tryFrom(strval($dbConfig["driver"] ?? ""));
                if (!$driver) {
                    throw new \OutOfBoundsException('Unsupported or invalid database driver');
                }

                // Other Validations...
                if (!NetworkValidator::isValidIpAddress($dbConfig["host"] ?? null, ipv4: true, ipv6: false)) {
                    throw new \DomainException(sprintf('Invalid IPv4 host for "%s" database', $dbId));
                }

                $port = $dbConfig["port"] ?? null;
                if (!is_int($port) || $port < 1000 || $port > 65535) {
                    throw new \OutOfRangeException(sprintf('Invalid connection port for "%s" database', $dbId));
                }

                $database = $dbConfig["database"] ?? null;
                if (!is_string($database) || !preg_match('/^[\w.\-]{3,32}$/', $database)) {
                    throw new \InvalidArgumentException(sprintf('Invalid DB name for "%s" database', $dbId));
                }

                $username = $dbConfig["username"] ?? null;
                if (!is_string($username)) {
                    throw new \InvalidArgumentException(sprintf('Invalid username for "%s" database', $dbId));
                }

                $password = $dbConfig["password"] ?? null;
                if (!is_string($password) && !is_null($password)) {
                    throw new \InvalidArgumentException(sprintf('Invalid password for "%s" database', $dbId));
                }

                // Append Configuration
                $dbConfigs->set($dbId, new DbCredentials(
                    $driver,
                    $database,
                    $dbConfig["host"],
                    $port,
                    $username,
                    $password,
                    persistent: false
                ));
            }
        }

        return $dbConfigs;
    }
}