<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\SystemAlerts;

/**
 * Interface AlertTraceProviderInterface
 * @package App\Shared\Foundation\CoreData\SystemAlerts
 */
interface AlertTraceProviderInterface
{
    public function getTraceInterface(): string;

    public function getTraceId(): ?int;
}