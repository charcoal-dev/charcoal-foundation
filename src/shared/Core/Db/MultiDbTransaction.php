<?php
declare(strict_types=1);

namespace App\Shared\Core\Db;

use App\Shared\CharcoalApp;
use App\Shared\Context\AppDatabase;
use Charcoal\Database\Database;

/**
 * Class MultiDbTransaction
 * @package App\Shared\Core\Db
 */
class MultiDbTransaction
{
    private array $databases = [];

    /**
     * @param CharcoalApp $app
     * @param AppDatabase ...$database
     */
    public function __construct(CharcoalApp $app, AppDatabase ...$database)
    {
        foreach ($database as $db) {
            $this->databases[$db->value] = $app->databases->getDb($db);
        }
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function beginTransaction(): void
    {
        $this->forEveryDb(function (Database $db) {
            $db->beginTransaction();
        }, throwOnFirst: true);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function commit(): void
    {
        $this->forEveryDb(function (Database $db) {
            $db->commit();
        }, throwOnFirst: true);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function rollBack(): void
    {
        $this->forEveryDb(function (Database $db) {
            $db->rollBack();
        }, throwOnFirst: false);
    }

    /**
     * @param \Closure $closure
     * @param bool $throwOnFirst
     * @return void
     * @throws \Exception
     */
    private function forEveryDb(\Closure $closure, bool $throwOnFirst = false): void
    {
        $caught = null;
        foreach ($this->databases as $db) {
            try {
                $closure($db);
            } catch (\Exception $e) {
                if ($throwOnFirst) {
                    throw $e;
                }

                if (!$caught) {
                    $caught = $e;
                }

                continue;
            }
        }

        if ($caught) {
            throw $caught;
        }
    }
}