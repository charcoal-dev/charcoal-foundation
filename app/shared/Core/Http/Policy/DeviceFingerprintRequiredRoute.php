<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Policy;

use Charcoal\Buffers\AbstractFixedLenBuffer;

/**
 * Interface DeviceFingerprintRequiredRoute
 * @package App\Shared\Core\Http\Policy
 */
interface DeviceFingerprintRequiredRoute
{
    public function resolveDeviceFp(): AbstractFixedLenBuffer;
}