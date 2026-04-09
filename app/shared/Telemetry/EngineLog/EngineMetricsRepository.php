<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\EngineLog;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Diagnostics\ExecutionMetrics;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Cli\Enums\ExecutionState;

/**
 * Repository class responsible for managing engine metrics within the underlying ORM system.
 * Handles operations such as inserting metrics data for a given engine log entry.
 */
final class EngineMetricsRepository extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(DatabaseTables::EngineMetrics,
            AppConstants::ORM_CACHE_ERROR_HANDLING);
    }

    /**
     * @throws WrappedException
     */
    public function capture(
        \DateTimeImmutable $timestamp,
        EngineLogEntity    $logEntity,
        ExecutionState     $state,
        ExecutionMetrics   $metrics
    ): void
    {
        try {
            $this->table->queryInsert([
                "id" => 0,
                "log_id" => $logEntity->id,
                "state" => $state->value,
                "logged_at" => (float)sprintf("%.6f", (float)$timestamp->format("U.u")),
                "memory_usage" => $metrics->memoryUsage,
                "memory_usage_peak" => $metrics->peakMemoryUsage,
                "cpu_time_user" => (int)($metrics->cpuTimeUser * 1e6),
                "cpu_time_system" => (int)($metrics->cpuTimeSystem * 1e6),
                "cpu_time_total" => (int)($metrics->cpuTimeTotal * 1e6),
            ]);
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to capture engine metrics");
        }
    }
}