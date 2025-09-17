<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

use App\Domain\AppManifest;
use App\Shared\Config\Snapshot\AppConfig;
use Charcoal\App\Kernel\AbstractApp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Internal\PathRegistry as Directories;

/**
 * Class CharcoalApp
 * @package App\Shared
 * @property PathRegistry $paths
 * @property AppConfig $config
 */
readonly class CharcoalApp extends AbstractApp
{
    //use InstanceOnStaticScopeTrait;

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
//        $data["coreData"] = $this->coreData;
//        $data["http"] = $this->http;
//        $data["mailer"] = $this->mailer;
//        $data["engine"] = $this->engine;
        return $data;
    }

    /**
     * @return void
     * @internal
     */
    protected function onReadyCallback(): void
    {
//        $this->coreData = $this->domain->get(AppBindings::coreData);
//        $this->http = $this->domain->get(AppBindings::http);
//        $this->mailer = $this->domain->get(AppBindings::mailer);
//        $this->engine = $this->domain->get(AppBindings::engine);

        //Initialize app on static scope
        //static::initializeStatic($this);
    }

    /**
     * @param AppEnv $env
     * @param Directories $paths
     * @return AppConfig
     */
    protected function resolveAppConfig(AppEnv $env, Directories $paths): AppConfig
    {
        return AppManifest::provideAppConfig($env, $paths);
    }

    /**
     * @return void
     */
    protected function errorServiceDeployedHook(): void
    {
    }

    /**
     * @return AppManifest
     */
    protected function resolveAppManifest(): AppManifest
    {
        return new AppManifest();
    }

    /**
     * @return class-string<CharcoalApp>
     */
    public static function getAppFqcn(): string
    {
        $appClassname = getenv("CHARCOAL_APP");
        if (!$appClassname) {
            throw new \RuntimeException("CHARCOAL_APP environment variable is not set");
        }

        $app = "\\App\\Domain\\" . $appClassname;
        return match (class_exists($app)) {
            true => $app,
            false => static::class,
        };
    }
}