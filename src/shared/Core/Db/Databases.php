<?php
declare(strict_types=1);

namespace App\Shared\Core\Db;


/**
 * Class Databases
 * @package App\Shared\Core\Db
 */
class Databases extends \Charcoal\App\Kernel\Databases
{
    public function getDb(Database|string $key): \Charcoal\Database\Database
    {
        return parent::getDb(is_string($key) ? $key : $key->value);
    }

    public function primary(): \Charcoal\Database\Database
    {
        return $this->getDb(Database::PRIMARY);
    }
}