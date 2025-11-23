<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\HttpIngress;

use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Http\Commons\Body\UnsafePayload;
use Charcoal\Http\Commons\Contracts\PayloadInterface;
use Charcoal\Http\Commons\Headers\Headers;
use Charcoal\Http\Commons\Headers\HeadersImmutable;
use Charcoal\Http\Server\Contracts\Controllers\Auth\AuthContextInterface;
use Charcoal\Http\Server\Contracts\Logger\RequestLogEntityInterface;
use Charcoal\Http\Server\Request\Bags\QueryParams;

/**
 * Represents an HTTP ingress log entity.
 * Extends the OrmEntityBase class and encapsulates the details of an HTTP request/response interaction.
 */
final class HttpIngressLogEntity extends OrmEntityBase implements
    RequestLogEntityInterface
{
    public int $id;
    public Interfaces $interface;
    public string $uuid;
    public string $ipAddress;
    public ?int $responseCode;
    public string $method;
    public string $urlScheme;
    public string $urlHost;
    public ?int $urlPort;
    public string $urlPath;
    public ?string $controller;
    public ?string $entrypoint;
    public ?string $requestHeaders;
    public ?string $requestParamsQuery;
    public ?string $requestParamsBody;
    public ?string $responseHeaders;
    public ?string $responseParams;
    public ?int $flagSid = null;
    public ?int $flagUid = null;
    public ?int $flagTid = null;
    public int $loggedAt;
    public ?int $duration;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }

    /**
     * @param string $controllerFqcn
     * @param string $entrypoint
     * @return void
     */
    public function setControllerMetadata(string $controllerFqcn, string $entrypoint): void
    {
        $this->controller = $controllerFqcn;
        $this->entrypoint = $entrypoint;
    }

    /**
     * @param HeadersImmutable $headers
     * @return void
     */
    public function setRequestHeaders(HeadersImmutable $headers): void
    {
        if ($headers->count() > 0) {
            $this->requestHeaders = json_encode($headers->getArray());
        }
    }

    /**
     * @param int $responseCode
     * @return void
     */
    public function setResponseCode(int $responseCode): void
    {
        $this->responseCode = $responseCode;
    }

    /**
     * @param QueryParams $queryParams
     * @param UnsafePayload|null $payload
     * @return void
     */
    public function setRequestParams(QueryParams $queryParams, ?UnsafePayload $payload): void
    {
        if ($queryParams->count() > 0) {
            $this->requestParamsQuery = json_encode($queryParams->getArray());
        }

        if ($payload && $payload->count() > 0) {
            $this->requestParamsBody = json_encode($payload->getArray());
        }
    }

    /**
     * @param Headers|HeadersImmutable $headers
     * @return void
     */
    public function setResponseHeaders(Headers|HeadersImmutable $headers): void
    {
        if ($headers->count() > 0) {
            $this->responseHeaders = json_encode($headers->getArray());
        }
    }

    /**
     * @param PayloadInterface|null $payload
     * @param string|null $cachedId
     * @return void
     */
    public function setResponseData(?PayloadInterface $payload, ?string $cachedId = null): void
    {
        if ($payload && $payload->count() > 0) {
            $this->responseParams = json_encode($payload->getArray());
        }
    }

    /**
     * @param float|null $startTime
     * @return void
     */
    public function finalizeLogEntity(?float $startTime): void
    {
        $this->duration = (int)((microtime(true) - $startTime) * 1e6);
    }

    /**
     * @param AuthContextInterface $authContext
     * @return void
     */
    public function setAuthenticationData(AuthContextInterface $authContext): void
    {
    }
}