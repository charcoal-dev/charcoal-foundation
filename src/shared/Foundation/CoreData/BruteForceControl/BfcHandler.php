<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\BruteForceControl;

use App\Shared\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;

/**
 * Class BfcHandler
 * @package App\Shared\Foundation\CoreData\BruteForceControl
 * @property CoreDataModule $module
 */
class BfcHandler extends AbstractOrmRepository
{
    /**
     * @param CoreDataModule $module
     */
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::BFC);
    }

    /**
     * @param BruteForceAction $action
     * @param string $caller
     * @return void
     * @throws \Charcoal\Database\Exception\QueryExecuteException
     */
    public function logEntry(BruteForceAction $action, string $caller): void
    {
        $this->table->getDb()->exec(
            "INSERT INTO `" . $this->table->name . "` (`action`, `caller`, `timestamp`)" .
            " VALUES (:action, :caller, :timestamp)",
            [
                "action" => strtolower($action->actionStr),
                "caller" => strtolower($caller),
                "timestamp" => time()
            ]
        );
    }

    /**
     * @param BruteForceAction|null $action
     * @param string|null $caller
     * @param int $timePeriod
     * @return int
     * @throws \Charcoal\Database\Exception\QueryExecuteException
     * @throws \Charcoal\Database\Exception\QueryFetchException
     */
    public function checkCount(BruteForceAction $action = null, string $caller = null, int $timePeriod = 3600): int
    {
        $queryStmt = "SELECT count(*) FROM `" . $this->table->name . "` WHERE `timestamp`>=?";
        $queryData = [(time() - $timePeriod)];
        if ($action) {
            $queryStmt .= " AND `action`=?";
            $queryData[] = $action->actionStr;
        }

        if (is_string($caller) && !empty($caller)) {
            $queryStmt .= " AND `caller`=?";
            $queryData[] = strtolower($caller);
        }

        if (count($queryData) <= 1) {
            throw new \LogicException("No action or caller provided to check BFC count");
        }

        $attempts = $this->table->getDb()->fetch($queryStmt, $queryData)->getNext();
        return (int)($attempts["count(*)"] ?? 0);
    }
}