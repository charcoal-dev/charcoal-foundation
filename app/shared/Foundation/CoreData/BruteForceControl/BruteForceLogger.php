<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\BruteForceControl;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use App\Shared\Security\BruteForce\BruteForceActor;
use App\Shared\Security\BruteForce\BruteForcePolicy;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;

/**
 * Handles logging and querying brute force attempts to a database table.
 * @property CoreDataModule $module
 */
final class BruteForceLogger extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(DatabaseTables::BruteForceControl);
    }

    /**
     * @throws \Charcoal\Database\Exceptions\QueryExecuteException
     * @api
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
     * Checks the count of brute force attempts based on the provided policy
     * and actor within a specified time period.
     * @throws \Charcoal\Database\Exceptions\QueryExecuteException
     * @throws \Charcoal\Database\Exceptions\QueryFetchException
     * @api
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