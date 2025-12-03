<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Bfc;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Security\BruteForceControl\BruteForceAction;
use App\Shared\Security\BruteForceControl\BruteForceActor;
use Charcoal\App\Kernel\Clock\Clock;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;

/**
 * Responsible for handling brute-force control (BFC) operations.
 * Manages logging and retrieving brute-force attempts based on actors and actions.
 */
final class BfcRepository extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(DatabaseTables::BruteForceControl, AppConstants::ORM_CACHE_ERROR_HANDLING);
    }

    /**
     * @throws \Charcoal\Database\Exceptions\QueryExecuteException
     */
    public function logEntry(
        BruteForceActor     $actor,
        BruteForceAction    $action,
        ?\DateTimeImmutable $timestamp = null
    ): void
    {
        if (!$timestamp) {
            $timestamp = Clock::now();
        }

        $this->table->getDb()->exec("INSERT INTO " . $this->table->name . " (action, actor, logged_at) "
            . "VALUES (:action, :actor, :timestamp)",
            [
                "action" => $action->value,
                "actor" => $actor->value,
                "timestamp" => $timestamp->getTimestamp()
            ]);
    }

    /**
     * @throws \Charcoal\Database\Exceptions\QueryExecuteException
     * @throws \Charcoal\Database\Exceptions\QueryFetchException
     */
    public function getCount(
        ?BruteForceActor    $actor = null,
        ?BruteForceAction   $action = null,
        int                 $duration = 3600,
        ?\DateTimeImmutable $timestamp = null
    ): int
    {
        if (!$actor && !$action) {
            throw new \BadMethodCallException("Either actor or action must be provided");
        }

        $timestamp = $timestamp ? $timestamp->getTimestamp() : Clock::getTimestamp();
        $queryStmt = "SELECT count(*) FROM " . $this->table->name . " WHERE logged_at>=?";
        $queryData = [($timestamp - $duration)];

        // Filter by BFC action
        if ($action) {
            $queryStmt .= " AND action=?";
            $queryData[] = $action->value;
        }

        // Filter by BFC actor
        if ($actor) {
            $queryStmt .= " AND actor=?";
            $queryData[] = $actor->value;
        }

        $attempts = $this->table->getDb()->fetch($queryStmt, $queryData)->getNext();
        return (int)($attempts["count(*)"] ?? 0);
    }
}