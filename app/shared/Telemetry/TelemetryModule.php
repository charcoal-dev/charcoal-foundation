<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry;

use App\Shared\CharcoalApp;
use App\Shared\Enums\SecretKeys;
use App\Shared\Telemetry\AppLogs\AppLogsRepository;
use App\Shared\Telemetry\AppLogs\AppLogsTable;
use App\Shared\Telemetry\EngineLog\EngineLogRepository;
use App\Shared\Telemetry\EngineLog\EngineLogTable;
use App\Shared\Telemetry\EngineLog\EngineMetricsRepository;
use App\Shared\Telemetry\EngineLog\EngineMetricsTable;
use App\Shared\Telemetry\HttpIngress\HttpIngressRepository;
use App\Shared\Telemetry\HttpIngress\HttpIngressTable;
use App\Shared\Traits\OrmModuleTrait;
use Charcoal\App\Kernel\Domain\ModuleSecurityBindings;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cipher\Cipher;

/**
 * Represents a telemetry module that extends the functionality of the application's ORM layer.
 * Provides repositories and database table registration for telemetry data, such as application logs and metrics.
 * Implements security bindings for module-specific operations.
 */
final class TelemetryModule extends OrmModuleBase
{
    use OrmModuleTrait;

    public readonly AppLogsRepository $appLogs;
    public readonly EngineLogRepository $engineLogs;
    public readonly EngineMetricsRepository $engineMetrics;
    public readonly HttpIngressRepository $httpIngress;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->appLogs = new AppLogsRepository();
        $this->engineLogs = new EngineLogRepository();
        $this->engineMetrics = new EngineMetricsRepository();
        $this->httpIngress = new HttpIngressRepository();
    }

    /**
     * @param TableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new AppLogsTable($this));
        $tables->register(new EngineLogTable($this));
        $tables->register(new EngineMetricsTable($this));
        $tables->register(new HttpIngressTable($this));
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->appLogs = $data["appLogs"];
        $this->engineLogs = $data["engineLogs"];
        $this->engineMetrics = $data["engineMetrics"];
        $this->httpIngress = $data["httpIngress"];
        parent::__unserialize($data);
    }

    /**
     * @return ModuleSecurityBindings
     */
    protected function declareSecurityBindings(): ModuleSecurityBindings
    {
        return new ModuleSecurityBindings(
            Cipher::AES_256_GCM,
            SecretKeys::Telemetry
        );
    }
}