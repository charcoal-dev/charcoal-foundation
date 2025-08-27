<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli\Process;

use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertLevel;

/**
 * Interface CrashSystemAlertInterface
 * @package App\Shared\Core\Cli\Process
 */
interface CrashSystemAlertInterface
{
    public function alertLevelOnCrash(): SystemAlertLevel;
}