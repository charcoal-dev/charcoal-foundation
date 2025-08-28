<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Core\Config\Builder\AppConfigBuilder;
use App\Shared\Core\Config\Snapshot\AppConfig;
use App\Shared\Core\ErrorManager;
use App\Shared\Core\PathRegistry;
use App\Shared\Foundation\CoreData\CoreDataModule;
use App\Shared\Foundation\Engine\EngineModule;
use App\Shared\Foundation\Http\HttpModule;
use App\Shared\Foundation\Mailer\MailerModule;
use Charcoal\App\Kernel\AppManifest;
use Charcoal\App\Kernel\Domain\AbstractModule;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Enums\SemaphoreType;
use Charcoal\Filesystem\Node\DirectoryNode;

/**
 * Represents the domain-specific application manifest.
 * Provides module binding, configuration provisioning, path resolution, and error service initialization.
 */
final class DomainManifest extends AppManifest
{
    /**
     *  DomainManifest constructor.
     */
    public function __construct()
    {
        $this->bind(AppBindings::coreData,
            fn(CharcoalApp $app) => $this->createDomainModule(AppBindings::coreData, $app));
        $this->bind(AppBindings::engine,
            fn(CharcoalApp $app) => $this->createDomainModule(AppBindings::engine, $app));
        $this->bind(AppBindings::http,
            fn(CharcoalApp $app) => $this->createDomainModule(AppBindings::http, $app));
        $this->bind(AppBindings::mailer,
            fn(CharcoalApp $app) => $this->createDomainModule(AppBindings::mailer, $app));
    }

    /**
     * @param AppEnv $env
     * @param PathRegistry $paths
     * @return AppConfig
     */
    public static function provideAppConfig(AppEnv $env, PathRegistry $paths): AppConfig
    {
        $appConfig = new AppConfigBuilder($env, $paths);
        $appConfig->security->setSemaphoreDirectory(SemaphoreType::Filesystem_Private, "./tmp/semaphore");
        $appConfig->security->setSemaphoreDirectory(SemaphoreType::Filesystem_Shared, "./shared/semaphore");
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
            AppBindings::engine => new EngineModule($app),
            AppBindings::http => new HttpModule($app),
            AppBindings::mailer => new MailerModule($app),
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
     * Resolve and initialize the error service.
     * @param PathRegistry $paths
     */
    public function resolveErrorService(
        AppEnv                                     $env,
        \Charcoal\App\Kernel\Internal\PathRegistry $paths
    ): ErrorManager
    {
        return new ErrorManager($env, $paths);
    }
}