<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

/**
 * Class ConcurrencyBinding
 * @package App\Shared\Core\Http
 */
readonly class ConcurrencyBinding
{
    public function __construct(
        public ConcurrencyPolicy $policy,
        public int               $maximumWaitTime = 3,
        public float             $tickInterval = 0.5
    )
    {
    }
}