<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

use App\Domain\AppManifest;
use App\Shared\Config\Snapshot\AppConfig;
use App\Shared\CoreData\CoreDataModule;
use App\Shared\Mailer\MailerModule;
use App\Shared\Security\SecurityService;
use App\Shared\Telemetry\TelemetryModule;
use Charcoal\App\Kernel\AbstractApp;
use Charcoal\App\Kernel\Diagnostics\LogEntry;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Enums\DiagnosticsEvent;
use Charcoal\App\Kernel\Internal\PathRegistry as Directories;

/**
 * Class CharcoalApp
 * @package App\Shared
 * @property PathRegistry $paths
 * @property AppConfig $config
 * @property SecurityService $security
 */
readonly class CharcoalApp extends AbstractApp
{
    public RuntimeConfig $runtime;
    public CoreDataModule $coreData;
    public TelemetryModule $telemetry;
    public MailerModule $mailer;

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["runtime"] = null;
        $data["coreData"] = null;
        $data["telemetry"] = null;
        $data["mailer"] = null;
        return $data;
    }

    /**
     * @param bool $restore
     * @return void
     */
    protected function beforeDomainBundlesHook(bool $restore): void
    {
        $this->runtime = new RuntimeConfig();
    }

    /**
     * @return void
     * @internal
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function onReadyCallback(): void
    {
        $this->coreData = $this->domain->get(AppBindings::coreData);
        $this->telemetry = $this->domain->get(AppBindings::telemetry);
        $this->mailer = $this->domain->get(AppBindings::mailer);

        // Capture log entries (includes Exceptions and Errors) and archive using the telemetry module
        if ($this->runtime->logAppLogs) {
            $logLevel = $this->runtime->appLogLevel->value;
            $this->events->diagnostics(
                DiagnosticsEvent::LogEntry,
                function (LogEntry $logEntry) use ($logLevel) {
                    if ($logLevel > $logEntry->level->value) {
                        return;
                    }

                    $loadedSapi = $this->sapi->current();
                    $this->telemetry->appLogs->store(
                        $loadedSapi->enum,
                        $loadedSapi->sapi->getCurrentUuid(),
                        $logEntry
                    );
                });
        }
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