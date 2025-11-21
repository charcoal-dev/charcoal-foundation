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
use App\Shared\Traits\OrmModuleTrait;
use Charcoal\App\Kernel\Domain\ModuleSecurityBindings;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cipher\Cipher;

final class TelemetryModule extends OrmModuleBase
{
    use OrmModuleTrait;

    public readonly AppLogsRepository $appLogs;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->appLogs = new AppLogsRepository();
    }

    /**
     * @param TableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new AppLogsTable($this));
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->appLogs = $data["appLogs"];
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