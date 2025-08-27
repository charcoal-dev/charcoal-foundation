<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\Core\Http\Exceptions\Api\ResponseFinalizedException;
use Charcoal\Http\Commons\Body\WritablePayload;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Response\PayloadResponse;

/**
 * Class ApiResponse
 * @package App\Shared\Core\Http\Api
 */
abstract class ApiResponse extends PayloadResponse
{
    protected const string PARAM_ERROR = "error";
    protected const string PARAM_SUCCESS = "isSuccess";

    protected bool $isSuccess = false;

    public function __construct()
    {
        parent::__construct(new WritableHeaders(), new WritablePayload(), ContentType::Json);
    }

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