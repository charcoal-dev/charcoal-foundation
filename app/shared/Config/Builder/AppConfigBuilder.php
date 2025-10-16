<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Builder;

use App\Shared\Config\Snapshot\AppConfig;
use App\Shared\Config\Traits\CacheConfigBuilderTrait;
use App\Shared\Config\Traits\DatabaseConfigBuilderTrait;
use App\Shared\Config\Traits\SapiConfigBuilderTrait;
use App\Shared\Config\Traits\SecurityConfigBuilderTrait;
use App\Shared\Enums\Timezones;
use App\Shared\Http\Client\HttpClientConfig;
use App\Shared\PathRegistry;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Support\JsonHelper;

/**
 * This class is responsible for initializing and aggregating various configuration builders,
 * including HTTP configuration and optional mailer configuration.
 * The configuration is read from YAML files and processed to build the final application configuration.
 */
final class AppConfigBuilder extends \Charcoal\App\Kernel\Config\Builder\AppConfigBuilder
{
    use CacheConfigBuilderTrait;
    use DatabaseConfigBuilderTrait;
    use SapiConfigBuilderTrait;
    use SecurityConfigBuilderTrait;

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
        $this->httpInterfacesFromFileConfig($configData["charcoal"]["http"]["sapi"] ?? null);
        $this->securityFromFileConfig($configData["charcoal"]["security"] ?? null);
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
            new HttpClientConfig()
        );
    }
}