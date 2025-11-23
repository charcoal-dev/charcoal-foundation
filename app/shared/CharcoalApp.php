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
 */
readonly class CharcoalApp extends AbstractApp
{
    public RuntimeConfig $runtime;
    public CoreDataModule $coreData;
    public TelemetryModule $telemetry;

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["runtime"] = null;
        $data["coreData"] = null;
        $data["telemetry"] = null;
        return $data;
    }

    /**
     * @return void
     * @internal
     * @noinspection PhpFieldAssignmentTypeMismatchInspection
     */
    protected function onReadyCallback(): void
    {
        $this->runtime = new RuntimeConfig();
        $this->coreData = $this->domain->get(AppBindings::coreData);
        $this->telemetry = $this->domain->get(AppBindings::telemetry);

        // Capture log entries (includes Exceptions and Errors) and archive using telemetry module
        if ($this->runtime->logAppLogs) {
            $charcoal = $this;
            $this->events->diagnostics(
                DiagnosticsEvent::LogEntry,
                function (LogEntry $logEntry) use ($charcoal) {
                    $charcoal->telemetry->appLogs->store(
                        $charcoal->sapi->current()->enum,
                        null,
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