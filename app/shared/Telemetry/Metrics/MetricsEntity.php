<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\Metrics;

use App\Shared\Telemetry\TelemetryType;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;

/**
 * Represents a metrics entity, which contains information regarding system
 * and application performance metrics at a specific point in time.
 */
final class MetricsEntity extends OrmEntityBase
{
    public int $id;
    public TelemetryType $type;
    public int $logId;
    public int $loggedAt;
    public int $memoryUsage;
    public int $memoryUsagePeak;
    public int $cpuTimeUser;
    public int $cpuTimeSystem;
    public int $cpuTimeTotal;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }
}