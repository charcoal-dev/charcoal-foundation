<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Countries;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\Contracts\Dataset\Sort;

/**
 * Class CountriesRepository
 * @package App\Shared\CoreData\Countries
 */
final class CountriesRepository extends OrmRepositoryBase
{
    public function __construct()
    {
        parent::__construct(
            DatabaseTables::Countries,
            AppConstants::ORM_CACHE_ERROR_HANDLING
        );
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function getSingle(string $code): ?CountryEntity
    {
        /** @var CountryEntity */
        return $this->getEntity($code, false, sprintf("code%d=?", strlen($code)), [$code], false);
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function getList(
        ?bool  $status = null,
        Sort   $sort = Sort::ASC,
        string $order = "name"
    ): array
    {
        try {
            return $this->getMultipleFromDb(
                is_bool($status) ? "status=?" : "1",
                is_bool($status) ? [$status] : [],
                $sort,
                $order
            );
        } catch (EntityNotFoundException) {
            return [];
        }
    }
}