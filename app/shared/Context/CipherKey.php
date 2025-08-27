<?php
declare(strict_types=1);

namespace App\Shared\Context;

use Charcoal\App\Kernel\Cipher\CipherEnum;

/**
 * Class CipherKey
 * @package App\Shared\Context
 */
enum CipherKey: string implements CipherEnum
{
    case PRIMARY = "primary";
    case DOMAIN = "domain";
}