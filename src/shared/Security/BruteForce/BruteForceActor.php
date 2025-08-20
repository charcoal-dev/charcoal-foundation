<?php
declare(strict_types=1);

namespace App\Shared\Security\BruteForce;

/**
 * Class BruteForceActor
 * @package App\Shared\Security\BruteForce
 */
readonly class BruteForceActor
{
    /**
     * @param string $actorIp
     * @param bool $hash
     * @return static
     * @api
     */
    public static function fromIpAddress(string $actorIp, bool $hash = true): static
    {
        $bin = @inet_pton(trim($actorIp));
        if ($bin === false) {
            throw new \InvalidArgumentException("Invalid IP address");
        }

        return new static("ip:" . ($hash ? md5($bin) : bin2hex($bin)));
    }

    /**
     * @param string $actorId
     */
    protected function __construct(public string $actorId)
    {
        if (strlen($actorId) > 45) {
            throw new \InvalidArgumentException("Brute force actor ID cannot exceed 45 characters");
        }
    }
}