<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Builder;

use App\Shared\Core\Config\Builder\Traits\CacheConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\DatabaseConfigBuilderTrait;
use App\Shared\Core\Config\Builder\Traits\MailerFileConfigTrait;
use App\Shared\Core\Config\Builder\Traits\SapiConfigBuilderTrait;
use App\Shared\Core\Config\Http\ClientConfig;
use App\Shared\Core\Config\Persisted\MailerConfig;
use App\Shared\Core\Config\Snapshot\AppConfig;
use App\Shared\Core\PathRegistry;
use App\Shared\Enums\Timezones;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Support\JsonHelper;

/**
 * This class is responsible for initializing and aggregating various configuration builders,
 * including HTTP configuration and optional mailer configuration.
 * The configuration is read from YAML files and processed to build the final application configuration.
 */
final class AppConfigBuilder extends \Charcoal\App\Kernel\Config\Builder\AppConfigBuilder
{
    public readonly ?MailerConfig $mailer;

    use CacheConfigBuilderTrait;
    use DatabaseConfigBuilderTrait;
    use SapiConfigBuilderTrait;
    use MailerFileConfigTrait;

    /**
     * @param AppEnv $env
     * @param PathRegistry $paths
     */
    public function __construct(AppEnv $env, PathRegistry $paths)
    {
        try {
            $configData = JsonHelper::jsonDecodeImports($paths->config, "charcoal");
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to load config file: " .
                $e->getMessage(), previous: $e);
        }

        parent::__construct($env, $paths, Timezones::from(strval($configData["charcoal"]["timezone"])));

        $this->cacheStoresFromFileConfig($configData["charcoal"]["cache"] ?? null);
        $this->databasesFromFileConfig($configData["charcoal"]["databases"] ?? null);
        $this->httpInterfacesFromFileConfig($configData["charcoal"]["sapi"] ?? null);
        $this->includeMailerConfig($configData["charcoal"]["mailer"] ?? null);
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
            $this->sapi->build(),
            new ClientConfig(),
            $this->mailer?->snapshot()
        );
    }
}