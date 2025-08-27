<?php
declare(strict_types=1);

namespace App\Shared\Core\Cache;

use App\Shared\CharcoalApp;
use App\Shared\Context\CacheStore;
use Charcoal\Cache\Cache;

/**
 * Class CachePool
 * @package App\Shared\Core\Cache
 * @property CharcoalApp $app
 */
class CachePool extends \Charcoal\App\Kernel\CachePool
{
    /**
     * @return Cache
     */
    public function primary(): Cache
    {
        return $this->get(CacheStore::PRIMARY);
    }
}