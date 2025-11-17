<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Countries;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;

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
}