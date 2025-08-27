<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\Countries;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityUpsertTrait;
use Charcoal\Base\Vectors\StringVector;

/**
 * Repository class responsible for handling CountryEntity objects in the data storage layer.
 * Extends functionality from the OrmRepositoryBase class and provides domain-specific logic.
 * @property CoreDataModule $module
 */
final class CountriesRepository extends OrmRepositoryBase
{
    use EntityUpsertTrait;

    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::Countries);
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function get(string $code2, bool $useCache): CountryEntity
    {
        /** @var CountryEntity */
        return $this->getEntity(strtoupper($code2), $useCache, "`code2`=?", [$code2], $useCache);
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @api
     */
    public function upsert(CountryEntity $country): int
    {
        return $this->dbUpsertEntity($country,
            new StringVector("status", "name", "region", "code3", "code2", "dialCode"));
    }
}