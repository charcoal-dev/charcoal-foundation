<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry;

use Charcoal\Base\Enums\Traits\EnumMappingTrait;

/**
 * Represents different types of telemetry data that can be processed by the system.
 */
enum TelemetryType: string
{
    case HttpIngress = "http_ingress";
    case HttpEgress = "http_egress";
    case EngineLog = "engine_log";
    case Internal = "internal";

    use EnumMappingTrait;
}