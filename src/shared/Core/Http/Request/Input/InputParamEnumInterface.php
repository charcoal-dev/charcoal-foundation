<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Request\Input;

use App\Shared\Core\Http\AbstractAppEndpoint;

/**
 * Interface InputParamEnumInterface
 * @package App\Shared\Core\Http\Request\Input
 */
interface InputParamEnumInterface extends \BackedEnum
{
    /**
     * @param AbstractAppEndpoint $context
     * @return string
     */
    public function resolveKey(AbstractAppEndpoint $context): string;
}