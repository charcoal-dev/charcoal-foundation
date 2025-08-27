<?php
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