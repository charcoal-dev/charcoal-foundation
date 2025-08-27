<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use App\Shared\Core\Orm\ModuleComponentEnum;

/**
 * Class Mailer
 * @package App\Shared\Foundation\Mailer
 */
enum Mailer implements ModuleComponentEnum
{
    case BACKLOG;
}