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
use Charcoal\Base\Support\Runtime;
use Charcoal\Http\Server\Contracts\Logger\LogStorageProviderInterface;
use Charcoal\Http\Server\Contracts\Logger\RequestLogEntityInterface;
use Charcoal\Http\Server\Request\Logger\RequestLogPolicy;
use Charcoal\Http\Server\Request\RequestGateway;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Class responsible for handling HTTP ingress data persistence.
 * This repository extends the base ORM repository and provides custom operations
 * for managing `HttpIngressLogEntity` entities.
 */
final class HttpIngressRepository extends OrmRepositoryBase implements
    LogStorageProviderInterface
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
    public function initLogEntity(
        RequestGateway $request,
        ?\Closure      $beforeInsert = null,
        array          $context = [],
    ): HttpIngressLogEntity
    {
        $interface = $context[0] ?? null;
        Runtime::assert($interface instanceof Interfaces, "Enum Interfaces not provided for: initLogEntity");

        $logEntity = new HttpIngressLogEntity();
        $logEntity->interface = $interface;
        $logEntity->uuid = $request->uuid;
        $logEntity->responseCode = null;

        $requestFacade = $request->requestFacade;
        $logEntity->ipAddress = $requestFacade->clientIp;
        $logEntity->method = $requestFacade->method->value;
        $logEntity->path = $request->request->url->path ?: "/";
        $logEntity->controller = null;
        $logEntity->entrypoint = null;
        $logEntity->requestHeaders = null;
        $logEntity->requestParamsQuery = null;
        $logEntity->requestParamsBody = null;
        $logEntity->responseHeaders = null;
        $logEntity->responseParams = null;
        $logEntity->flagSid = null;
        $logEntity->flagUid = null;
        $logEntity->flagTid = null;
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
    public function finishLogEntity(RequestLogPolicy $policy, RequestLogEntityInterface $logEntity): void
    {
        $changeLog = new StringVector();
        $changeLog->append("controller", "entrypoint", "requestHeaders", "requestParamsQuery", "requestParamsBody",
            "responseHeaders", "responseParams", "flagSid", "flagUid", "flagTid", "duration", "responseCode");
        $this->dbUpdateEntity($logEntity, $changeLog, $logEntity->id, "id");
    }
}