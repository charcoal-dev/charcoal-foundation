<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Persistence;

use App\Shared\Contracts\PersistedConfigInterface;
use Charcoal\App\Kernel\Entity\AbstractEntity;

/**
 * @api Abstract class for persisted config objects.
 */
abstract class AbstractPersistedConfig extends AbstractEntity
    implements PersistedConfigInterface
{
    use PersistedConfigObjectTrait;
}