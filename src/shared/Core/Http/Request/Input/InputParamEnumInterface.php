<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Input;

use App\Shared\Core\Http\AppAwareEndpoint;

/**
 * Interface InputParamEnumInterface
 * @package App\Shared\Core\Http\Request\Input
 */
interface InputParamEnumInterface extends \BackedEnum
{
    /**
     * @param AppAwareEndpoint $context
     * @return string
     */
    public function resolveKey(AppAwareEndpoint $context): string;
}