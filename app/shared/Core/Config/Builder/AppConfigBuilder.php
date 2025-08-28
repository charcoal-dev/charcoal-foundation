<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Builder;

use App\Shared\Core\Config\Builder\Traits\CacheConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\DatabaseConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\JsonConfigReaderTrait;
use App\Shared\Core\Config\Builder\Traits\MailerFileConfigTrait;
use App\Shared\Core\Config\Persisted\MailerConfig;
use App\Shared\Core\Config\Snapshot\AppConfig;
use App\Shared\Core\PathRegistry;
use App\Shared\Enums\Timezones;
use Charcoal\App\Kernel\Enums\AppEnv;

/**
 * This class is responsible for initializing and aggregating various configuration builders,
 * including HTTP configuration and optional mailer configuration.
 * The configuration is read from YAML files and processed to build the final application configuration.
 */
final class AppConfigBuilder extends \Charcoal\App\Kernel\Config\Builder\AppConfigBuilder
{
    public readonly HttpConfigBuilder $http;
    public readonly ?MailerConfig $mailer;

    use CacheConfigBuilderTrait;
    use DatabaseConfigBuilderTrait;
    use JsonConfigReaderTrait;
    use MailerFileConfigTrait;

    /**
     * @param AppEnv $env
     * @param PathRegistry $paths
     */
    public function __construct(AppEnv $env, PathRegistry $paths)
    {
        parent::__construct($env, $paths, Timezones::from(strval($configData["timezone"])));

        $this->cacheStoresFromFileConfig($configData["foundation"]["cache"] ?? null);
        $this->databasesFromFileConfig($configData["foundation"]["databases"] ?? null);

        $this->http = new HttpConfigBuilder();
        $this->httpInterfacesFromFileConfig($configData["foundation"]["http"] ?? null);

        $this->includeMailerConfig($configData["foundation"]["mailer"] ?? null);
        if (!isset($this->mailer)) {
            $this->mailer = null;
        }
    }

    /**
     * @param mixed $mailerConfig
     * @return void
     */
    protected function includeMailerConfig(mixed $mailerConfig): void
    {
        $mailerConfig = $this->getMailerConfig($mailerConfig);
        if ($mailerConfig) {
            $this->mailer = $mailerConfig;
        }
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