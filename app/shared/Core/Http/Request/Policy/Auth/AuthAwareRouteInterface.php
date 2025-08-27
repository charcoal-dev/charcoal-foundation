<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Policy\Auth;

/**
 * Interface AuthAwareRouteInterface
 * @package App\Shared\Core\Http\Auth
 */
interface AuthAwareRouteInterface
{
    public function resolveAuthContext(): AuthContextInterface;
}