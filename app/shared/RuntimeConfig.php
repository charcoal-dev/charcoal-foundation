<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

/**
 * Represents the runtime configuration settings for CharcoalApp.
 */
final class RuntimeConfig
{
    public bool $telemetryModule = false;
    public bool $telemetryAppLogs = false;
    public bool $telemetryHttpIngress = false;
}