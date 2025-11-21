<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\ServerApi\ServerApiEnumInterface;
use Charcoal\Base\Enums\Traits\EnumMappingTrait;
use Charcoal\Contracts\Sapi\SapiType;

/**
 * Defines the Interfaces enumeration which represents different types of SAPI (Server API) interfaces.
 * Implements the SapiEnumInterface for standardization.
 */
enum Interfaces: string implements ServerApiEnumInterface
{
    case Engine = "engine";
    case Web = "web";

    use EnumMappingTrait;

    /**
     * Determines the SAPI (Server API) type based on the current instance.
     */
    public function type(): SapiType
    {
        return match ($this) {
            self::Web => SapiType::Http,
            self::Engine => SapiType::Cli,
        };
    }
}