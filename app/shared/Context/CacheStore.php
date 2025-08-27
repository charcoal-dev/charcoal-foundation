<?php
declare(strict_types=1);

namespace App\Shared\Context;

use Charcoal\App\Kernel\Module\CacheStoreEnum;

/**
 * Class CacheStore
 * @package App\Shared\Context
 */
enum CacheStore: string implements CacheStoreEnum
{
    case PRIMARY = "primary";

    /**
     * @return string
     */
    public function getServerKey(): string
    {
        return $this->value;
    }
}