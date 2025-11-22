<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\Metrics;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Diagnostics\ExecutionMetrics;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;

/**
 * A repository class designed to handle operations related to application metrics
 * within the defined ORM infrastructure.
 */
final class MetricsRepository extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(
            DatabaseTables::AppMetrics,
            AppConstants::ORM_CACHE_ERROR_HANDLING
        );
    }

    /**
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     */
    public function insert(Interfaces $interface, ?string $uuid, ExecutionMetrics $metrics): void
    {
        $this->table->queryInsert([
            "id" => 0,
            "interface" => $interface->value,
            "uuid" => $uuid,
            "loggedAt" => (int)$metrics->timestamp,
            "memoryUsage" => $metrics->memoryUsage,
            "memoryUsagePeak" => $metrics->peakMemoryUsage,
            "cpuTimeUser" => (int)($metrics->cpuTimeUser * 1e6),
            "cpuTimeSystem" => (int)($metrics->cpuTimeSystem * 1e6),
            "cpuTimeTotal" => (int)($metrics->cpuTimeTotal * 1e6),
        ]);
    }
}