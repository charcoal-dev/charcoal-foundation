<?php
declare(strict_types=1);

namespace App\Shared\Contracts\Accounts;

/**
 * Interface RealmAccountInterface
 * @package App\Shared\Contracts\Accounts
 */
interface RealmAccountInterface
{
    public function getAccountId(): int;

    public function getRealm(): AccountRealm;
}