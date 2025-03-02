<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine\ExecutionLog;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\Engine\EngineModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Exception\EntityOrmException;

/**
 * Class LogStatsOrm
 * @package App\Shared\Foundation\Engine\ExecutionLog
 */
class LogStatsOrm extends AbstractOrmRepository
{
    /**
     * @param EngineModule $module
     */
    public function __construct(EngineModule $module)
    {
        parent::__construct($module, AppDbTables::ENGINE_EXEC_STATS);
    }

    /**
     * @param ExecutionLogEntity $log
     * @return void
     * @throws EntityOrmException
     */
    public function upsert(ExecutionLogEntity $log): void
    {
        try {
            $this->table->getDb()->exec(
                sprintf("DELETE FROM `%s` WHERE `log`=? AND `state`=?", $this->table->name),
                [$log->id, $log->state->value]
            );
        } catch (\Exception $e) {
            throw new EntityOrmException(static::class, $e);
        }

        $this->insert($log);
    }

    /**
     * @param ExecutionLogEntity $log
     * @return void
     * @throws EntityOrmException
     */
    public function insert(ExecutionLogEntity $log): void
    {
        try {
            $this->table->queryInsert([
                "log" => $log->id,
                "state" => $log->state->value,
                "cpu_load" => (int)(intval(sys_getloadavg()[0]) * 100),
                "memory_usage" => memory_get_usage(false),
                "memory_usage_real" => memory_get_usage(true),
                "peak_memory_usage" => memory_get_peak_usage(false),
                "peak_memory_usage_real" => memory_get_peak_usage(true),
                "timestamp" => microtime(true)
            ]);
        } catch (\Exception $e) {
            throw new EntityOrmException(static::class, $e);
        }
    }
}