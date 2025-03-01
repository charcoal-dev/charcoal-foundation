<?php
declare(strict_types=1);

namespace App\Shared\Core;

use Charcoal\App\Kernel\Cipher\CipherEnum;

/**
 * Class CipherKey
 * @package App\Shared\Core
 */
enum CipherKey: string implements CipherEnum
{
    case PRIMARY = "primary";
    case DOMAIN = "domain";
}