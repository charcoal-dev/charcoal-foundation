<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Persistence;

use App\Shared\Contracts\PersistedConfigInterface;
use Charcoal\App\Kernel\Entity\AbstractEntityImmutable;

/**
 * @api Abstract class for Immutable persisted config objects.
 */
abstract readonly class AbstractPersistedConfigImmutable extends AbstractEntityImmutable implements
    PersistedConfigInterface
{
    use PersistedConfigObjectTrait;
}