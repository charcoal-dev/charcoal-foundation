<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\CoreData\Internal;

/**
 * Interface CoreDataConstants
 * @package App\Shared\CoreData\Internal
 */
interface CoreDataConstants
{
    /** language=RegExp */
    public const string STORED_OBJECT_REF_REGEXP = "/\A[a-zA-Z0-9][a-zA-Z0-9\-_.]{1,39}\z/";
}