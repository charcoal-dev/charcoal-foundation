<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\Core\Http\AbstractApiEndpoint;
use App\Shared\Core\Http\Auth\AuthContextResolverInterface;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogEntity;
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
     * @param int $statusCode
     * @return $this
     */
    public function setSuccess(bool $status, int $statusCode = 200): static
    {
        if ($statusCode) {
            $this->setStatusCode($statusCode);
        }

        $this->isSuccess = $status;
        return $this;
    }

    /**
     * @param AbstractApiEndpoint $route
     * @param AuthContextResolverInterface|null $authContext
     * @param InterfaceLogEntity|null $logEntity
     * @return void
     */
    public function prepareResponseCallback(
        AbstractApiEndpoint           $route,
        ?AuthContextResolverInterface $authContext,
        ?InterfaceLogEntity           $logEntity,
    ): void
    {
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