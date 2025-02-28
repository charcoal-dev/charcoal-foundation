<?php
declare(strict_types=1);

namespace App\Shared\Utility;

use Charcoal\Buffers\AbstractByteArray;

/**
 * Class PasswordHelper
 * @package App\Shared\Utility
 */
class PasswordHelper
{
    /**
     * @param string|AbstractByteArray $password
     * @param int $memoryCost
     * @param int $timeCost
     * @param int $threads
     * @return string
     */
    public static function hashArgon2(
        string|AbstractByteArray $password,
        int                      $memoryCost = 65536,
        int                      $timeCost = 4,
        int                      $threads = 2
    ): string
    {
        if ($password instanceof AbstractByteArray) {
            $password = $password->raw();
        }

        return password_hash($password, PASSWORD_ARGON2ID, [
            "memory_cost" => $memoryCost,
            "time_cost" => $timeCost,
            "threads" => $threads
        ]);
    }

    /**
     * @param string|AbstractByteArray $password
     * @param string $hash
     * @return bool
     */
    public static function verifyArgon2(string|AbstractByteArray $password, string $hash): bool
    {
        if ($password instanceof AbstractByteArray) {
            $password = $password->raw();
        }

        return password_verify($password, $hash);
    }

    /**
     * NULL-safe password hashing with BCRYPT
     * @param string|AbstractByteArray $password
     * @return string
     */
    public static function hashBcrypt(string|AbstractByteArray $password): string
    {
        if ($password instanceof AbstractByteArray) {
            $password = str_replace("\0", "\1", $password->raw());
        }

        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * @param string|AbstractByteArray $password
     * @param string $hash
     * @return bool
     */
    public static function verifyBcrypt(string|AbstractByteArray $password, string $hash): bool
    {
        if ($password instanceof AbstractByteArray) {
            $password = str_replace("\0", "\1", $password->raw());
        }

        return password_verify($password, $hash);
    }
}