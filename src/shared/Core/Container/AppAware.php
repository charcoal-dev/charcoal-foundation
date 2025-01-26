<?php
declare(strict_types=1);

namespace App\Shared\Core\Container;

use App\Shared\CharcoalApp;

/**
 * Class AppAware
 * @package App\Shared\Core\Container
 */
abstract class AppAware
{
    protected readonly CharcoalApp $app;

    public function bootstrap(CharcoalApp $app): void
    {
        $this->app = $app;
    }

    abstract protected function collectSerializableData(): array;

    abstract protected function onUnserialize(array $data): void;

    final public function __serialize(): array
    {
        return $this->collectSerializableData();
    }

    final public function __unserialize(array $data): void
    {
        $this->onUnserialize($data);
    }
}