<?php
declare(strict_types=1);

namespace App\Shared\Security\BruteForce;

/**
 * Class BruteForcePolicy
 * @package App\Shared\Core\Http\Policy\BruteForce
 */
readonly class BruteForcePolicy
{
    public string $actionStr;

    public function __construct(
        string      $actionStr,
        public int  $maxAttempts,
        public int  $withinSeconds,
        public bool $enforced = true
    )
    {
        if (!preg_match('/^\w{6,64}$/', $actionStr)) {
            throw new \InvalidArgumentException("Invalid action string for " . static::class);
        }

        $this->actionStr = strtolower($actionStr);
        if ($this->maxAttempts <= 0 || $this->withinSeconds < 0) {
            throw new \InvalidArgumentException("maxAttempts/withinSeconds must be > 0");
        }
    }
}