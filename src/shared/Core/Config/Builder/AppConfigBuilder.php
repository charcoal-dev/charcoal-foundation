<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Builder;

use App\Shared\Core\Config\Builder\Traits\CacheConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\DatabaseConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\HttpFileConfigTrait;
use App\Shared\Core\Config\Builder\Traits\YamlConfigFilesTrait;
use App\Shared\Core\Config\CiphersConfig;
use App\Shared\Core\Config\Snapshot\AppConfig;
use App\Shared\Core\Config\StaticMailerConfigTrait;
use App\Shared\Core\PathRegistry;
use App\Shared\Enums\Timezones;
use App\Shared\Foundation\Mailer\Config\MailerConfig;
use Charcoal\App\Kernel\Enums\AppEnv;

/**
 * ConfigFromYaml is responsible for building application configuration from a YAML or JSON file.
 * It extends the AppConfigBuilder class and integrates configurations for mailer, HTTP,
 * encryption ciphers, cache, and database settings.
 */
final class AppConfigBuilder extends \Charcoal\App\Kernel\Config\Builder\AppConfigBuilder
{
    public readonly HttpConfigBuilder $http;

    public readonly ?MailerConfig $mailer;
    public readonly ?CiphersConfig $ciphers;

    use YamlConfigFilesTrait;
    use CacheConfigBuilderTrait;
    use DatabaseConfigBuilderTrait;

    use HttpFileConfigTrait;

    use StaticMailerConfigTrait;

    /**
     * @throws \Charcoal\Yaml\Exception\YamlParseException
     */
    public function __construct(AppEnv $env, PathRegistry $paths)
    {
        $configData = $this->readYamlConfigFiles($paths->config->absolute . "/config.yaml");

        parent::__construct(
            $env,
            Timezones::from(strval($configData["timezone"])),
            $this->getCacheConfigBuilder($configData["foundation"]["cache"] ?? null),
            $this->getDatabasesConfig($configData["foundation"]["databases"] ?? null)
        );

        $this->http = new HttpConfigBuilder();
        $this->httpInterfacesFromFileConfig($configData["foundation"]["http"] ?? null);
    }

    public function build(): AppConfig
    {
        return new AppConfig(
            $this->env,
            $this->timezone,
            $this->cache?->build(),
            $this->database?->build(),
            $this->http->build()
        );
    }
}