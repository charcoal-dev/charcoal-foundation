<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Policy;

use Charcoal\Buffers\AbstractFixedLenBuffer;

/**
 * Interface DeviceFingerprintRequiredRoute
 * @package App\Shared\Core\Http\Policy
 */
interface DeviceFingerprintRequiredRoute
{
    public function resolveDeviceFp(): AbstractFixedLenBuffer;
}