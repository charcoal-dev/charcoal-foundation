<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\AppLogs;

use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;

/**
 * This entity is used to record log messages, their corresponding levels,
 * and other contextual information for debugging or monitoring purposes.
 */
final class AppLogEntity extends OrmEntityBase
{
    public int $id;
    public Interfaces $interface;
    public string $sapi;
    public ?string $uuid;
    public AppLogLevel $level;
    public string $message;
    public ?string $context;
    public ?string $exception;
    public int $loggedAt;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }
}