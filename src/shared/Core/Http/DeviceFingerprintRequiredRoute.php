<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Buffers\AbstractFixedLenBuffer;

/**
 * Interface DeviceFingerprintRequiredRoute
 * @package App\Shared\Core\Http
 */
interface DeviceFingerprintRequiredRoute
{
    public function resolveDeviceFp(): AbstractFixedLenBuffer;
}