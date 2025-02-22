<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Auth;

use App\Shared\Foundation\Http\InterfaceLog\RouteLogTraceProvider;

/**
 * Interface AuthContextResolverInterface
 * @package App\Shared\Core\Http\Auth
 */
interface AuthContextResolverInterface extends RouteLogTraceProvider
{
    /**
     * Primary session or token identifier
     * @return int
     */
    public function getPrimaryId(): int;
}