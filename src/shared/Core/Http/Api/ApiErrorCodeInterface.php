<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\Core\Http\AbstractApiEndpoint;

/**
 * Interface ApiErrorCodeInterface
 * @package App\Shared\Core\Http\Api
 */
interface ApiErrorCodeInterface extends \BackedEnum
{
    public function getErrorMessage(AbstractApiEndpoint $context): ?string;

    public function getHttpCode(): ?int;
}