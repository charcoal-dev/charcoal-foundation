<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

/**
 * Interface ApiErrorCodeInterface
 * @package App\Shared\Core\Http\Api
 */
interface ApiErrorCodeInterface extends \BackedEnum
{
    /**
     * First argument received is always instance of AbstractApiEndpoint
     * @return string|null
     */
    public function getErrorMessage(): ?string;

    /**
     * @return int|null
     */
    public function getHttpCode(): ?int;
}