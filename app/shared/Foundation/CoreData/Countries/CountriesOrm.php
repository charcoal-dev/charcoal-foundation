<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\Countries;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Repository\EntityUpsertTrait;
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class CountriesOrm
 * @package App\Shared\Foundation\CoreData\Countries
 * @property CoreDataModule $module
 */
class CountriesOrm extends AbstractOrmRepository
{
    use EntityUpsertTrait;

    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::COUNTRIES);
    }

    /**
     * @param string $code2
     * @param bool $useCache
     * @return CountryEntity
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function get(string $code2, bool $useCache): CountryEntity
    {
        /** @var CountryEntity */
        return $this->getEntity(strtoupper($code2), $useCache, "`code2`=?", [$code2], $useCache);
    }

    /**
     * @param CountryEntity $country
     * @return int
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function upsert(CountryEntity $country): int
    {
        return $this->dbUpsertEntity($country,
            new StringVector("status", "name", "region", "code3", "code2", "dialCode"));
    }

    /**
     * @param CountryEntity|string $code2OrEntity
     * @return void
     * @throws \Charcoal\Cache\Exception\CacheException
     */
    public function deleteFromCache(CountryEntity|string $code2OrEntity): void
    {
        $this->cacheDeleteEntity($code2OrEntity);
    }
}