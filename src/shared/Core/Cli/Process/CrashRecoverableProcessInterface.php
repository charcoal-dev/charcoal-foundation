<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Process;

/**
 * Interface CrashRecoverableProcessInterface
 * @package App\Shared\Core\Cli\Process
 */
interface CrashRecoverableProcessInterface
{
    public function isRecoverable(): bool;

    public function recoveryOnConstructHook(): void;

    public function handleRecoveryAfterCrash(): void;
}