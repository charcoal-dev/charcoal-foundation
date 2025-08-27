<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Input;

/**
 * Class InputParamFlag
 * @package App\Shared\Core\Http\Request\Input
 */
enum ParamFlag: int
{
    case REQUIRED = 1 << 0;
    case VALIDATED = 1 << 1;
    case SPECIAL = 1 << 2;
    case SENSITIVE = 1 << 3;
    case INTERNAL = 1 << 4;
    case DEPRECATED = 1 << 5;
}