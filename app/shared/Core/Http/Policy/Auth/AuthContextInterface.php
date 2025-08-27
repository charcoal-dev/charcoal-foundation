<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Policy\Auth;

use App\Shared\Foundation\Http\InterfaceLog\RouteLogTraceProvider;

/**
 * Interface AuthContextInterface
 * @package App\Shared\Core\Http\Policy\Auth
 */
interface AuthContextInterface extends RouteLogTraceProvider
{
    /**
     * Primary session or token identifier
     * @return int
     */
    public function getPrimaryId(): int;

    /**
     * @return void
     */
    public function onSendResponseCallback(): void;
}