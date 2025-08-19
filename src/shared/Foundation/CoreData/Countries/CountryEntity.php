<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\Countries;

use Charcoal\App\Kernel\Contracts\Orm\Entity\CacheableEntityInterface;
use Charcoal\App\Kernel\Orm\Entity\CacheableEntityTrait;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;

/**
 * This class provides functionality for identifying and serializing country data.
 * It implements the CacheableEntityInterface to allow caching capabilities.
 * The trait CacheableEntityTrait is included for additional functionality.
 */
class CountryEntity extends OrmEntityBase implements CacheableEntityInterface
{
    public bool $status;
    public string $name;
    public string $region;
    public string $code3;
    public string $code2;
    public string $dialCode;

    use CacheableEntityTrait;

    public function getPrimaryId(): string
    {
        return $this->code2;
    }

    protected function collectSerializableData(): array
    {
        return [
            "status" => $this->status,
            "name" => $this->name,
            "region" => $this->region,
            "code3" => $this->code3,
            "code2" => $this->code2,
            "dialCode" => $this->dialCode,
        ];
    }
}