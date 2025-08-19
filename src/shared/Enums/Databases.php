<?php
declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\DatabaseEnumInterface;

/**
 * An enumeration representing databases embedded with app.
 */
enum Databases: string implements DatabaseEnumInterface
{
    case Primary = "primary";

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->value;
    }
}