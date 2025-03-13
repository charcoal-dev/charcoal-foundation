<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Auth;

/**
 * Interface AuthRouteInterface
 * @package App\Shared\Core\Http\Auth
 */
interface AuthRouteInterface
{
    public function resolveAuthContext(): AuthContextResolverInterface;
}