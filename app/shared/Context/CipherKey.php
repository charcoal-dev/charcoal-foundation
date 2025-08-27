<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Context;

/**
 * Class CipherKey
 * @package App\Shared\Context
 * @deprecated
 */
enum CipherKey: string
{
    case PRIMARY = "primary";
    case DOMAIN = "domain";
}