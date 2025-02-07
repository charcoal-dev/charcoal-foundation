<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\BruteForceControl;

/**
 * Class BruteForceAction
 * @package App\Shared\Foundation\CoreData\BruteForceControl
 */
class BruteForceAction
{
    public readonly string $actionStr;

    public function __construct(string $actionStr)
    {
        if (!preg_match('/^\w{6,64}$/i', $actionStr)) {
            throw new \LogicException("Invalid action string for " . static::class);
        }

        $this->actionStr = strtolower($actionStr);
    }
}