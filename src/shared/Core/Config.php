<?php
declare(strict_types=1);

namespace App\Shared\Core;

use App\Shared\Core\Config\HttpStaticConfig;
use App\Shared\Core\Config\StaticMailerConfigTrait;
use App\Shared\Foundation\Mailer\Config\MailerConfig;
use App\Shared\Utility\NetworkValidator;
use Charcoal\App\Kernel\Config\CacheConfig;
use Charcoal\App\Kernel\Config\CacheDriver;
use Charcoal\App\Kernel\Config\CacheServerConfig;
use Charcoal\App\Kernel\Config\DbConfigs;
use Charcoal\App\Kernel\DateTime\Timezone;
use Charcoal\Database\DbCredentials;
use Charcoal\Database\DbDriver;
use Charcoal\Yaml\Parser;

/**
 * Class Config
 * @package App\Shared\Core
 */
class Config extends \Charcoal\App\Kernel\Config
{
    public readonly ?MailerConfig $mailer;
    public readonly ?HttpStaticConfig $http;

    use StaticMailerConfigTrait;

    /**
     * @param Directories $dir
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function __construct(Directories $dir)
    {
        $configDir = $dir->config;
        $configData = (new Parser(evaluateBooleans: true, evaluateNulls: true))
            ->getParsed($configDir->pathToChild("/config.yml", false));

        parent::__construct(
            Timezone::from(strval($configData["timezone"])),
            $this->getCacheConfig($configData["core"]["cache"] ?? null),
            $this->getDatabasesConfig($configData["core"]["databases"] ?? null),
        );

        $this->http = new HttpStaticConfig($dir, $configData["http"] ?? null);
        if (property_exists($this, "mailer")) {
            $this->mailer = method_exists($this, "getMailerConfig") ?
                $this->getMailerConfig($configData["mailer"] ?? null) : null;
        }
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
     * @param array|null $configData
     * @return CacheConfig
     */
    private function getCacheConfig(array|null $configData): CacheConfig
    {
        $cacheConfig = new CacheConfig();
        $cachePool = $configData["pool"] ?? null;

        if (is_array($cachePool)) {
            foreach ($cachePool as $poolId => $cacheServer) {
                // Database Instance Key
                if (!is_string($poolId) || !preg_match('/^\w{2,20}$/', $poolId)) {
                    throw new \InvalidArgumentException('Invalid label for cache server object in YML');
                }

                // Cache Driver
                $driver = CacheDriver::tryFrom(strval($cacheServer["driver"]));
                if (!$driver) {
                    throw new \OutOfBoundsException("Invalid cache driver in configuration");
                }

                if ($driver === CacheDriver::NULL) {
                    $cacheConfig->set($poolId, new CacheServerConfig(CacheDriver::NULL, "0.0.0.0", 0, 0));
                    continue;
                }

                // Validations on host, port and timeout
                if (!NetworkValidator::isValidIpAddress($cacheServer["host"] ?? null, ipv4: true, ipv6: false)) {
                    throw new \DomainException(sprintf('Invalid IPv4 host address for "%s" cache server', $poolId));
                }

                $port = $cacheServer["port"] ?? null;
                if (!is_int($port) || $port < 1000 || $port > 65535) {
                    throw new \InvalidArgumentException(sprintf('Invalid configured port for "%s" cache server', $poolId));
                }

                $timeout = $cacheServer["timeout"] ?? null;
                if (!is_int($timeout) || $timeout < 1 || $timeout > 6) {
                    throw new \OutOfRangeException(sprintf('Invalid configured timeout value for "%s" cache server', $poolId));
                }

                $cacheConfig->set($poolId, new CacheServerConfig($driver, $cacheServer["host"], $port, $timeout));
            }
        }

        return $cacheConfig;
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