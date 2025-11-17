<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Countries;

use App\Shared\CoreData\CoreDataModule;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Class CountriesTable
 * @package App\Shared\CoreData\Countries
 */
final class CountriesTable extends OrmTableBase
{
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, DatabaseTables::Countries, CountryEntity::class);
    }

    /**
     * @param ColumnsBuilder $cols
     * @param ConstraintsBuilder $constraints
     * @return void
     */
    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->bool("status")->default(false);
        $cols->string("name")->length(40);
        $cols->string("region")->length(40);
        $cols->string("code2")->fixed(2)->unique();
        $cols->string("code3")->fixed(3)->unique();
        $cols->string("dial_code")->length(8)->matchRegExp("/^\+[1-9][0-9]*(-[0-9]+)*$/");
    }

    /**
     * @param TableMigrations $migrations
     * @return void
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}