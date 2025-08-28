<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\SapiEnumInterface;
use Charcoal\App\Kernel\Enums\SapiType;

/**
 * Defines the Interfaces enumeration which represents different types of SAPI (Server API) interfaces.
 * Implements the SapiEnumInterface for standardization.
 */
enum Interfaces implements SapiEnumInterface
{
    case Engine;
    case Web;

    /**
     * Determines the SAPI (Server API) type based on the current instance.
     */
    public function getType(): SapiType
    {
        return match ($this) {
            self::Web => SapiType::Http,
            self::Engine => SapiType::Cli,
        };
    }
}