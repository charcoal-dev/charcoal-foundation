<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\Core\Http\AbstractApiEndpoint;
use App\Shared\Core\Http\Auth\AuthContextResolverInterface;
use App\Shared\Exception\ApiResponseFinalizedException;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogEntity;
use Charcoal\Http\Router\Controllers\Response\PayloadResponse;

/**
 * Class ApiResponse
 * @package App\Shared\Core\Http\Api
 */
class ApiResponse extends PayloadResponse
{
    protected const string PARAM_ERROR = "error";
    protected const string PARAM_SUCCESS = "isSuccess";

    protected bool $isSuccess = false;

    /**
     * @param bool $status
     * @param int $statusCode
     * @return never
     * @throws ApiResponseFinalizedException
     */
    public function setSuccess(bool $status, int $statusCode = 200): never
    {
        if ($statusCode) {
            $this->setStatusCode($statusCode);
        }

        $this->isSuccess = $status;
        throw new ApiResponseFinalizedException();
    }

    /**
     * @param string|array $error
     * @param int|null $statusCode
     * @return never
     * @throws ApiResponseFinalizedException
     */
    public function setError(string|array $error, ?int $statusCode = 400): never
    {
        if (!$statusCode) {
            $statusCode = 400;
        }

        $this->isSuccess = false;
        $this->setStatusCode($statusCode);
        $this->set(static::PARAM_ERROR, $error);
        throw new ApiResponseFinalizedException();
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
        return [static::PARAM_SUCCESS => $this->isSuccess, ...$body];
    }
}