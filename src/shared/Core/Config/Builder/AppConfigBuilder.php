<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Builder;

use App\Shared\Core\Config\Builder\Traits\CacheConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\DatabaseConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\HttpFileConfigTrait;
use App\Shared\Core\Config\Builder\Traits\MailerFileConfigTrait;
use App\Shared\Core\Config\Builder\Traits\YamlConfigFilesTrait;
use App\Shared\Core\Config\Persisted\MailerConfig;
use App\Shared\Core\Config\Snapshot\AppConfig;
use App\Shared\Core\PathRegistry;
use App\Shared\Enums\Timezones;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Filesystem\Path\DirectoryPath;

/**
 * This class is responsible for initializing and aggregating various configuration builders,
 * including HTTP configuration and optional mailer configuration.
 * The configuration is read from YAML files and processed to build the final application configuration.
 */
final class AppConfigBuilder extends \Charcoal\App\Kernel\Config\Builder\AppConfigBuilder
{
    public readonly HttpConfigBuilder $http;
    public readonly ?MailerConfig $mailer;

    use YamlConfigFilesTrait;
    use CacheConfigBuilderTrait;
    use DatabaseConfigBuilderTrait;
    use HttpFileConfigTrait;
    use MailerFileConfigTrait;

    /**
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function __construct(AppEnv $env, DirectoryPath $root, PathRegistry $paths)
    {
        $configData = $this->readYamlConfigFiles($paths->config->absolute . "/config.yaml");
        parent::__construct($env, $root, Timezones::from(strval($configData["timezone"])));

        $this->cacheStoresFromFileConfig($configData["foundation"]["cache"] ?? null);
        $this->databasesFromFileConfig($configData["foundation"]["databases"] ?? null);

        $this->http = new HttpConfigBuilder();
        $this->httpInterfacesFromFileConfig($configData["foundation"]["http"] ?? null);

        $this->includeMailerConfig($configData["foundation"]["mailer"] ?? null);
        if (!isset($this->mailer)) {
            $this->mailer = null;
        }

        $this->security->setSemaphoreDirectory("/tmp/semaphore");
    }

    /**
     * @api
     */
    protected function includeMailerConfig(mixed $mailerConfig): void
    {
        $this->mailer = $this->getMailerConfig($mailerConfig);
    }

    /**
     * @return AppConfig
     */
    public function build(): AppConfig
    {
        return new AppConfig(
            $this->env,
            $this->timezone,
            $this->cache->build(),
            $this->database->build(),
            $this->security->build(),
            $this->http->build(),
            $this->mailer?->snapshot()
        );
    }
}