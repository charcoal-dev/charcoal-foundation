<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\Core\Http\AbstractApiEndpointAbstract;
use App\Shared\Core\Http\Exception\Api\ResponseFinalizedException;
use App\Shared\Core\Http\Request\Policy\Auth\AuthContextInterface;
use App\Shared\Foundation\Http\InterfaceLog\LogEntity;
use Charcoal\Http\Router\Response\PayloadResponse;

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
     * @param int|null $statusCode
     * @return never
     * @throws ResponseFinalizedException
     * @api
     */
    public function setSuccess(?int $statusCode = 200): never
    {
        if (!$statusCode) {
            $statusCode = 200;
        }

        $this->isSuccess = true;
        $this->setStatusCode($statusCode);
        throw new ResponseFinalizedException();
    }

    /**
     * @param string|array $error
     * @param int|null $statusCode
     * @return never
     * @throws ResponseFinalizedException
     */
    public function setError(string|array $error, ?int $statusCode = 400): never
    {
        if (!$statusCode) {
            $statusCode = 400;
        }

        $this->isSuccess = false;
        $this->setStatusCode($statusCode);
        $this->set(static::PARAM_ERROR, $error);
        throw new ResponseFinalizedException();
    }

    /**
     * @param AbstractApiEndpointAbstract $route
     * @param AuthContextInterface|null $authContext
     * @param LogEntity|null $logEntity
     * @return void
     */
    public function prepareResponseCallback(
        AbstractApiEndpointAbstract $route,
        ?AuthContextInterface       $authContext,
        ?LogEntity                  $logEntity,
    ): void
    {
    }

    /**
     * @return string
     */
    protected function getBody(): string
    {
        $payload = $this->payload->getArray();
        $payload = [static::PARAM_SUCCESS => $this->isSuccess, ...$payload];
        return json_encode($payload);
    }

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["isSuccess"] = $this->isSuccess;
        return $data;
    }

    /**
     * @return class-string[]
     */
    public static function unserializeDependencies(): array
    {
        return [static::class, ...parent::unserializeDependencies()];
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->isSuccess = $data["isSuccess"];
        parent::__unserialize($data);
    }
}