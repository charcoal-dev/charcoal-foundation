<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Policy\Concurrency;

use App\Shared\Core\Http\Auth\AuthContextResolverInterface;

/**
 * Class ConcurrencyBinding
 * @package App\Shared\Core\Http
 */
readonly class ConcurrencyPolicy
{
    public function __construct(
        public ConcurrencyScope $scope,
        public int              $maximumWaitTime = 3,
        public float            $tickInterval = 0.5
    )
    {
    }

    /**
     * @param string $ip
     * @param AuthContextResolverInterface|null $authContext
     * @return string|null
     */
    public function getScopeLockId(string $ip, ?AuthContextResolverInterface $authContext): ?string
    {
        return match ($this->scope) {
            ConcurrencyScope::NONE => null,
            ConcurrencyScope::IP_ADDR => "ip_" . md5($ip),
            ConcurrencyScope::AUTH_CONTEXT => "auth_" . ($authContext?->getPrimaryId() ??
                    throw new \LogicException("Auth context required for ConcurrencyScope::AUTH_CONTEXT"))
        };
    }
}