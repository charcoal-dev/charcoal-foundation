<?php
declare(strict_types=1);

namespace App\Shared\Enums\Http;

use Charcoal\App\Kernel\Internal\Config\ConfigEnumInterface;
use Charcoal\Base\Traits\EnumMappingTrait;

/**
 * Defines an HTTP interface enumeration used to represent different types of HTTP interfaces.
 * The enumeration includes predefined values that specify the various HTTP interfaces.
 */
enum HttpInterface: string implements ConfigEnumInterface
{
    case Web = "web";

    use EnumMappingTrait;

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return strtolower($this->name);
    }
}