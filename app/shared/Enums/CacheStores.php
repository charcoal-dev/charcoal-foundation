<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\CacheStoreEnumInterface;
use Charcoal\Base\Enums\Traits\EnumFindCaseTrait;

/**
 * Class CacheStores
 * @package App\Shared\Enums
 */
enum CacheStores: string implements CacheStoreEnumInterface
{
    use EnumFindCaseTrait;

    case Primary = "primary";

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->value;
    }
}