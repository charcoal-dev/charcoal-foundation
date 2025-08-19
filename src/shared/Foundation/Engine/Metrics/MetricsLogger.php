<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine\Metrics;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\Engine\EngineModule;
use App\Shared\Foundation\Engine\Logs\LogEntity;
use Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;

/**
 * Handles logging of execution metrics into the database.
 * @property EngineModule $module
 */
final class MetricsLogger extends OrmRepositoryBase
{
    /**
     * @param EngineModule $module
     */
    public function __construct(EngineModule $module)
    {
        parent::__construct($module, DatabaseTables::EngineExecMetrics);
    }

    /**
     * @throws EntityRepositoryException
     */
    public function upsert(LogEntity $log): void
    {
        try {
            $this->table->getDb()->exec(
                sprintf("DELETE FROM `%s` WHERE `log`=? AND `state`=?", $this->table->name),
                [$log->id, $log->state->value]
            );
        } catch (\Exception $e) {
            throw new EntityRepositoryException($this, $e);
        }

        $this->insert($log);
    }

    /**
     * @throws EntityRepositoryException
     */
    public function insert(LogEntity $log): void
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
            throw new EntityRepositoryException($this, $e);
        }
    }
}