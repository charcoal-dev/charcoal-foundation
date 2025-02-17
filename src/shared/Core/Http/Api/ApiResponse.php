<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use Charcoal\Http\Router\Controllers\Response\PayloadResponse;

/**
 * Class ApiResponse
 * @package App\Shared\Core\Http\Api
 */
class ApiResponse extends PayloadResponse
{
    protected bool $isSuccess = false;

    /**
     * @param bool $status
     * @return $this
     */
    public function setSuccess(bool $status): static
    {
        $this->setStatusCode($status ? 200 : 400);
        $this->isSuccess = $status;
        return $this;
    }

    /**
     * @return array
     */
    protected function getBodyArray(): array
    {
        $body = parent::getBodyArray();
        return ["isSuccess" => $this->isSuccess, ...$body];
    }
}