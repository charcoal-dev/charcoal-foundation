<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Domain\Core\Http;

use Charcoal\Base\Objects\Traits\InstanceOnStaticScopeTrait;
use Charcoal\Http\Server\Middleware\MiddlewareRegistry;

/**
 * This class extends MiddlewareRegistry and represents the global pipelines
 * functionality within the application. It uses the InstanceOnStaticScopeTrait
 * to ensure proper static scoping behaviors are applied.
 */
final class GlobalPipelines extends MiddlewareRegistry
{
    use InstanceOnStaticScopeTrait;

    /**
     * @return void
     */
    protected function onConstructHook(): void
    {
        self::initializeStatic($this);
    }
}