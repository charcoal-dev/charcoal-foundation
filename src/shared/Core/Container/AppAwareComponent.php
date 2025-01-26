<?php
declare(strict_types=1);

namespace App\Shared\Core\Container;

use App\Shared\CharcoalApp;

/**
 * Class AppAwareComponent
 * @package App\Shared\Core\Container
 */
abstract class AppAwareComponent extends AppAware
{
    protected const array APP_AWARE_CHILDREN = [];

    public function bootstrap(CharcoalApp $app): void
    {
        parent::bootstrap($app);
        foreach (static::APP_AWARE_CHILDREN as $child) {
            ($this->$child)->bootstrap($app);
        }
    }
}