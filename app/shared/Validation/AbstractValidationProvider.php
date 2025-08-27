<?php
declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\Utility\StringHelper;

/**
 * Class AbstractValidationProvider
 * @package App\Shared\Validation
 */
abstract class AbstractValidationProvider
{
    /**
     * @param callable $validator
     * @param mixed $input
     * @return string|null
     */
    protected static function validatedStringOrNull(callable $validator, mixed $input): ?string
    {
        $input = StringHelper::getTrimmedOrNull($input);
        if ($input === null) {
            return null;
        }

        $validator($input);
        return $input;
    }
}