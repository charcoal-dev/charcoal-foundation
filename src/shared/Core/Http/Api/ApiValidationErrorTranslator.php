<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\Validation\ValidationErrorEnumInterface;

/**
 * Class ApiValidationErrorTranslator
 * @package App\Shared\Core\Http\Api
 */
class ApiValidationErrorTranslator
{
    public static function toApiError(ValidationErrorEnumInterface $error): ?ApiErrorCodeInterface
    {
        return null;
    }
}