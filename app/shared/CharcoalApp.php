<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Constants\AppConstants;
use App\Shared\Core\Config\Snapshot\AppConfig;
use App\Shared\Core\PathRegistry;
use App\Shared\Foundation\CoreData\CoreDataModule;
use App\Shared\Foundation\Engine\EngineModule;
use App\Shared\Foundation\Http\HttpModule;
use App\Shared\Foundation\Mailer\MailerModule;
use Charcoal\App\Kernel\AbstractApp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Internal\PathRegistry as Directories;
use Charcoal\App\Kernel\Support\Errors\FileErrorLogger;

/**
 * Class CharcoalApp
 * @package App\Shared
 * @property PathRegistry $paths
 * @property AppConfig $config
 */
class CharcoalApp extends AbstractApp
{
    //use InstanceOnStaticScopeTrait;

    public readonly CoreDataModule $coreData;
    public readonly HttpModule $http;
    public readonly MailerModule $mailer;
    public readonly EngineModule $engine;

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["coreData"] = $this->coreData;
        $data["http"] = $this->http;
        $data["mailer"] = $this->mailer;
        $data["engine"] = $this->engine;
        return $data;
    }

    /**
     * @return void
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     * @internal
     */
    protected function onReadyCallback(): void
    {
        $this->coreData = $this->domain->get(AppBindings::coreData);
        $this->http = $this->domain->get(AppBindings::http);
        $this->mailer = $this->domain->get(AppBindings::mailer);
        $this->engine = $this->domain->get(AppBindings::engine);

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
        return DomainManifest::provideAppConfig($env, $paths);
    }

    /**
     * @return DomainManifest
     */
    protected function resolveAppManifest(): DomainManifest
    {
        return new DomainManifest();
    }

    /**
     * @return void
     */
    protected function errorHandlersDeployedHook(): void
    {
        $this->errors->subscribe(new FileErrorLogger(AppConstants::ERROR_SINK,
            useAnsiEscapeSeq: AppConstants::ERROR_SINK_ANSI));
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