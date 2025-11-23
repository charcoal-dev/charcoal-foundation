<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Domain;

use App\Shared\AppBindings;
use App\Shared\CharcoalApp;
use App\Shared\Config\Builder\AppConfigBuilder;
use App\Shared\Config\Snapshot\AppConfig;
use App\Shared\CoreData\CoreDataModule;
use App\Shared\Enums\CacheStores;
use App\Shared\Enums\Databases;
use App\Shared\Enums\Interfaces;
use App\Shared\Enums\SecretsStores;
use App\Shared\Enums\SemaphoreProviders;
use App\Shared\Enums\SemaphoreScopes;
use App\Shared\PathRegistry;
use App\Shared\Telemetry\TelemetryModule;
use Charcoal\App\Kernel\Domain\AbstractModule;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Enums\EnumContract;
use Charcoal\App\Kernel\Security\SecurityService;
use Charcoal\Filesystem\Node\DirectoryNode;

/**
 * Represents the domain-specific application manifest.
 * Provides module binding, configuration provisioning, path resolution, and error service initialization.
 */
final class AppManifest extends \Charcoal\App\Kernel\AppManifest
{
    /**
     *  DomainManifest constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->bind(AppBindings::coreData,
            fn(CharcoalApp $app) => $this->createDomainModule(AppBindings::coreData, $app));
        $this->bind(AppBindings::telemetry,
            fn(CharcoalApp $app) => $this->createDomainModule(AppBindings::telemetry, $app));

        // HTTP Server(s)
        $this->httpServer(new WebRouter(Interfaces::Web));

        // Concrete Enums
        $this->enums->declare(EnumContract::CacheStoreEnum, CacheStores::class)
            ->declare(EnumContract::DbEnum, Databases::class)
            ->declare(EnumContract::SecretsStoreEnum, SecretsStores::class)
            ->declare(EnumContract::SemaphoreProviderEnum, SemaphoreProviders::class)
            ->declare(EnumContract::SemaphoreScopeEnum, SemaphoreScopes::class);
    }

    /**
     * @param AppEnv $env
     * @param PathRegistry $paths
     * @return AppConfig
     */
    public static function provideAppConfig(AppEnv $env, \Charcoal\App\Kernel\Internal\PathRegistry $paths): AppConfig
    {
        $appConfig = new AppConfigBuilder($env, $paths);
        return $appConfig->build();
    }

    /**
     * @param AppBindings $module
     * @param CharcoalApp $app
     * @return AbstractModule
     */
    protected function createDomainModule(AppBindings $module, CharcoalApp $app): AbstractModule
    {
        return match ($module) {
            AppBindings::coreData => new CoreDataModule($app),
            AppBindings::telemetry => new TelemetryModule($app),
            default => throw new \DomainException("Cannot build domain module"),
        };
    }

    /**
     * Resolves and returns a PathRegistry
     * instance based on the provided environment and directory root.
     */
    public function resolvePathsRegistry(AppEnv $env, DirectoryNode $root): PathRegistry
    {
        return new PathRegistry($env, $root->path);
    }

    /**
     * @return SecurityService
     */
    final protected function createSecurityService(): SecurityService
    {
        return new \App\Shared\Security\SecurityService();
    }
}