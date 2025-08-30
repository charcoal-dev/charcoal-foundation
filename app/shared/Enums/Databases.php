<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\DatabaseEnumInterface;

/**
 * An enumeration representing databases embedded with app.
 */
enum Databases: string implements DatabaseEnumInterface
{
    case Primary = "charcoal";

    /**
     * @return string
     */
    public function getConfigKey(): string
    {
        return $this->value;
    }
}