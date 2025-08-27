<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Policy\Concurrency;

use App\Shared\Core\Http\Request\Policy\Auth\AuthContextInterface;

/**
 * Class ConcurrencyPolicy
 * @package App\Shared\Core\Http\Policy\Concurrency
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
     * @param AuthContextInterface|null $authContext
     * @return string|null
     */
    public function getScopeLockId(string $ip, ?AuthContextInterface $authContext): ?string
    {
        return match ($this->scope) {
            ConcurrencyScope::NONE => null,
            ConcurrencyScope::IP_ADDR => "ip_" . md5($ip),
            ConcurrencyScope::AUTH_CONTEXT => "auth_" . ($authContext?->getPrimaryId() ??
                    throw new \LogicException("Auth context required for ConcurrencyScope::AUTH_CONTEXT"))
        };
    }
}