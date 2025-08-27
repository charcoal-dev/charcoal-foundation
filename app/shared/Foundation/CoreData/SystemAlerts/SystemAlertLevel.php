<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\SystemAlerts;

use Charcoal\OOP\Traits\EnumOptionsTrait;

/**
 * Class SystemAlertLevel
 * @package App\Shared\Foundation\CoreData\SystemAlerts
 */
enum SystemAlertLevel: string
{
    case CRITICAL = "critical";
    case ERROR = "error";
    case NOTICE = "notice";
    case INFO = "info";
    case DEBUG = "debug";

    use EnumOptionsTrait;
}