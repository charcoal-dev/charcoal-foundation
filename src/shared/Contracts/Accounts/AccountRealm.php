<?php
declare(strict_types=1);

namespace App\Shared\Contracts\Accounts;

use Charcoal\OOP\Traits\EnumOptionsTrait;

/**
 * Class AccountRealm
 * @package App\Shared\Contracts
 */
enum AccountRealm: string
{
    case ADMIN = "admin";
    case USER = "user";

    use EnumOptionsTrait;
}