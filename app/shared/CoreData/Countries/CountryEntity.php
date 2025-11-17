<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Countries;

use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;

/**
 * Class CountryEntity
 * @package App\Shared\CoreData\Countries
 */
final class CountryEntity extends OrmEntityBase
{
    public bool $status;
    public string $name;
    public string $region;
    public string $code2;
    public string $code3;
    public string $dialCode;

    /**
     * Use ISO Alpha-2 digit code as primary entity identifier
     */
    public function getPrimaryId(): string
    {
        return $this->code2;
    }

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["status"] = $this->status;
        $data["name"] = $this->name;
        $data["region"] = $this->region;
        $data["code2"] = $this->code2;
        $data["code3"] = $this->code3;
        $data["dialCode"] = $this->dialCode;
        return $data;
    }
}