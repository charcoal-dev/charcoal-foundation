<?php
declare(strict_types=1);

namespace App\Shared\Core\Orm;

use App\Shared\Exception\WrappedException;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\Queries\SortFlag;
use Charcoal\OOP\OOP;

/**
 * Class EntityPaginatedFetch
 * @package App\Shared\Core\Orm
 */
readonly class EntityPaginatedFetch
{
    public int $totalCount;
    public array $entities;
    public int $count;

    /**
     * @param AbstractOrmTable $table
     * @param array $whereQuery
     * @param array $whereData
     * @param SortFlag $sortFlag
     * @param string $sortColumn
     * @param int $page
     * @param int $perPage
     * @param \Closure|null $forEachRow
     * @throws WrappedException
     */
    public function __construct(
        AbstractOrmTable $table,
        array            $whereQuery,
        array            $whereData,
        public SortFlag  $sortFlag,
        string           $sortColumn = "id",
        public int       $page = 1,
        public int       $perPage = 100,
        ?\Closure        $forEachRow = null,
    )
    {
        if ($this->page < 1 || $this->perPage < 1) {
            throw new \InvalidArgumentException("Invalid pagination arguments");
        }

        $whereQuery = $whereQuery ? implode(" AND ", $whereQuery) : "1";
        $this->totalCount = $this->getTotalRowCount($table, $whereQuery, $whereData);
        if (!$this->totalCount) {
            $this->entities = [];
            $this->count = 0;
            return;
        }

        try {
            $entities = $table->queryFind(
                $whereQuery,
                $whereData,
                null,
                $sortFlag,
                $sortColumn,
                offset: ($this->page * $this->perPage) - $this->perPage,
                limit: $this->perPage,
            )->getAll();
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to retrieve paginated entities for table: " .
                OOP::baseClassName($table::class));
        }

        $result = [];
        foreach ($entities as $entity) {
            if ($forEachRow) {
                $entity = $forEachRow($entity);
                if ($entity) {
                    $result[] = $entity;
                }

                continue;
            }

            $result[] = $entity;
        }

        $this->entities = $result;
        $this->count = count($result);
    }

    /**
     * @param AbstractOrmTable $table
     * @param string $whereQuery
     * @param array $whereData
     * @return int
     * @throws WrappedException
     */
    private function getTotalRowCount(AbstractOrmTable $table, string $whereQuery, array $whereData): int
    {
        try {
            $totalCount = $table->getDb()
                ->fetch(sprintf('SELECT count(*) FROM `%s` WHERE %s', $table->name, $whereQuery), $whereData)
                ->getNext();
            $totalCount = intval($totalCount["count(*)"] ?? -1);
            if ($totalCount < 0) {
                throw new \RuntimeException("Cannot read count(*)");
            }

            return $totalCount;
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to retrieve total row count for table: " .
                OOP::baseClassName($table::class));
        }
    }
}