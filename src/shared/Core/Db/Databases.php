<?php
declare(strict_types=1);

namespace App\Shared\Core\Db;

/**
 * Class Databases
 * @package App\Shared\Core\Db
 */
class Databases extends \Charcoal\App\Kernel\Databases
{
    public function primary(): \Charcoal\Database\Database
    {
        return $this->getDb(AppDatabase::PRIMARY);
    }
}