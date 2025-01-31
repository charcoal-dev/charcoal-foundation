<?php
declare(strict_types=1);

namespace App\Shared\Core\Db;

use Charcoal\App\Kernel\Orm\Db\DatabaseEnum;

/**
 * An enumeration representing databases embedded with app.
 */
enum AppDatabase: string implements DatabaseEnum
{
    case PRIMARY = "primary";

    /**
     * @return string
     */
    public function getDatabaseKey(): string
    {
        return $this->value;
    }
}