<?php
declare(strict_types=1);

namespace App\Shared\Core\Cache;

use Charcoal\App\Kernel\Module\CacheStoreEnum;

/**
 * Class CacheStore
 * @package App\Shared\Core\Cache
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