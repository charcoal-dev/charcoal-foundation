<?php
declare(strict_types=1);

namespace App\Shared\Contracts;

/**
 * Interface RouteLogTraceProvider
 * @package App\Shared\Foundation\Http\InterfaceLog
 */
interface RouteLogTraceProvider
{
    public function getTraceSid(): ?int;

    public function getTraceUid(): ?int;

    public function getTraceTid(): ?int;
}