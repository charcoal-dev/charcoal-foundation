<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api\Error;

use App\Shared\Validation\ValidationErrorEnumInterface;

/**
 * Class ValidationErrorTranslator
 * @package App\Shared\Core\Http\Api\Error
 */
class ValidationErrorTranslator
{
    public static function getTranslated(ValidationErrorEnumInterface $error): ?ApiTranslatedErrorInterface
    {
        return null;
    }
}