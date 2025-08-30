<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\Countries;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Base\Enums\Charset;
use Charcoal\Database\Orm\Schema\Columns;
use Charcoal\Database\Orm\Schema\Constraints;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Represents the CountriesTable, defining the database structure and migrations
 * for managing country-related data.
 * @property CoreDataModule $module
 */
final class CountriesTable extends OrmTableBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::Countries, CountryEntity::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->bool("status")->default(false);
        $cols->string("name")->length(40);
        $cols->string("region")->length(40);
        $cols->string("code3")->fixed(3)->unique();
        $cols->string("code2")->fixed(2)->unique();
        $cols->string("dial_code")->length(8);
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}