<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\HttpIngress;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Clock\Clock;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityUpdatableTrait;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Class responsible for handling HTTP ingress data persistence.
 * This repository extends the base ORM repository and provides custom operations
 * for managing `HttpIngressLogEntity` entities.
 */
final class HttpIngressRepository extends OrmRepositoryBase
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    public function __construct()
    {
        parent::__construct(
            DatabaseTables::HttpIngress,
            AppConstants::ORM_CACHE_ERROR_HANDLING
        );
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function createLogEntity(
        Interfaces     $interface,
        string         $uuid,
        RequestGateway $request,
        ?\Closure      $beforeInsert = null
    )
    {
        $logEntity = new HttpIngressLogEntity();
        $logEntity->interface = $interface;
        $logEntity->uuid = $uuid;
        $logEntity->responseCode = null;

        $requestFacade = $request->requestFacade;
        $logEntity->ipAddress = $requestFacade->clientIp;
        $logEntity->method = $requestFacade->method->value;
        $urlInfo = $request->request->url;
        $logEntity->urlScheme = $urlInfo->scheme === "https" ? "https" : "http";
        $logEntity->urlHost = $urlInfo->host;
        $logEntity->urlPath = $urlInfo->path;
        $logEntity->urlPort = is_int($urlInfo->port) && $urlInfo->port > 0 && $urlInfo->port < 65536 ?
            $urlInfo->port : null;
        $logEntity->controller = null;
        $logEntity->entrypoint = null;
        $logEntity->requestHeaders = null;
        $logEntity->requestParamsQuery = null;
        $logEntity->requestParamsBody = null;
        $logEntity->responseHeaders = null;
        $logEntity->responseParams = null;
        $logEntity->responseCachedId = null;
        $logEntity->loggedAt = Clock::now()->getTimestamp();
        $logEntity->duration = null;

        if ($beforeInsert) {
            $beforeInsert($logEntity);
        }

        $this->dbInsertAndSetId($logEntity, "id");
        return $logEntity;
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function updateLogEntity(HttpIngressLogEntity $logEntity, StringVector $changeLog): void
    {
        $this->dbUpdateEntity($logEntity, $changeLog, $logEntity->id, "id");
    }
}