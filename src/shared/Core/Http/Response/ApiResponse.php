<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Response;

use Charcoal\Http\Router\Controllers\Response\PayloadResponse;

/**
 * Class ApiResponse
 * @package App\Shared\Core\Http\Response
 */
class ApiResponse extends PayloadResponse
{
    protected bool $isSuccess = false;

    /**
     * @param bool $status
     * @return void
     */
    public function setSuccess(bool $status): void
    {
        $this->isSuccess = $status;
    }
}