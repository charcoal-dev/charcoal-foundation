<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\BruteForceControl;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use App\Shared\Security\BruteForce\BruteForceActor;
use App\Shared\Security\BruteForce\BruteForcePolicy;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;

/**
 * Class BruteForceLogger
 * @package App\Shared\Foundation\CoreData\BruteForceControl
 * @property CoreDataModule $module
 */
class BruteForceLogger extends AbstractOrmRepository
{
    /**
     * @param CoreDataModule $module
     */
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::BFC);
    }

    /**
     * @param BruteForcePolicy $policy
     * @param BruteForceActor $actor
     * @return void
     * @throws \Charcoal\Database\Exception\QueryExecuteException
     */
    public function logEntry(BruteForcePolicy $policy, BruteForceActor $actor): void
    {
        $this->table->getDb()->exec(
            "INSERT INTO `" . $this->table->name . "` (`action`, `actor`, `timestamp`)" .
            " VALUES (:action, :actor, :timestamp)",
            [
                "action" => strtolower($policy->actionStr),
                "actor" => strtolower($actor->actorId),
                "timestamp" => time()
            ]
        );
    }

    /**
     * @param BruteForcePolicy|null $policy
     * @param BruteForceActor|null $actor
     * @param int $timePeriod
     * @return int
     * @throws \Charcoal\Database\Exception\QueryExecuteException
     * @throws \Charcoal\Database\Exception\QueryFetchException
     */
    public function checkCount(BruteForcePolicy $policy = null, BruteForceActor $actor = null, int $timePeriod = 3600): int
    {
        $queryStmt = "SELECT count(*) FROM `" . $this->table->name . "` WHERE `timestamp`>=?";
        $queryData = [(time() - $timePeriod)];
        if ($policy) {
            $queryStmt .= " AND `action`=?";
            $queryData[] = $policy->actionStr;
        }

        if ($actor) {
            $queryStmt .= " AND `actor`=?";
            $queryData[] = $actor->actorId;
        }

        if (count($queryData) <= 1) {
            throw new \LogicException("No action or actor ID provided to check BFC count");
        }

        $attempts = $this->table->getDb()->fetch($queryStmt, $queryData)->getNext();
        return (int)($attempts["count(*)"] ?? 0);
    }
}