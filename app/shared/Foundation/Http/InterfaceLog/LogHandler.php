<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http\InterfaceLog;

use App\Shared\Contracts\RouteLogTraceProvider;
use App\Shared\Core\Http\AbstractAppEndpoint;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityUpdatableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Class handling interface logging operations.
 * Provides functionality for creating, updating, and retrieving interface log entries.
 * @property HttpModule $module
 */
final class LogHandler extends OrmRepositoryBase
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    public function __construct()
    {
        parent::__construct(DatabaseTables::HttpInterfaceLog);
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function createLog(
        AbstractAppEndpoint    $route,
        ?RequestSnapshot       $snapshot,
        ?RouteLogTraceProvider $traceProvider = null
    ): LogEntity
    {
        $requestLog = new LogEntity();
        $requestLog->interface = $route->interface->enum;
        $requestLog->ipAddress = $route->userIpAddress;
        $requestLog->method = $route->request->method;
        $requestLog->endpoint = $route->request->url->path ?? "/";
        $requestLog->startOn = round(microtime(true), 4);
        $requestLog->endOn = null;
        $requestLog->alerts = 0;
        $requestLog->responseCode = null;
        $requestLog->flagSid = $traceProvider?->getTraceSid();
        $requestLog->flagUid = $traceProvider?->getTraceUid();
        $requestLog->flagTid = $traceProvider?->getTraceTid();
        $requestLog->snapshot = $snapshot ? new Buffer(serialize($snapshot)) : null;
        $this->dbInsertAndSetId($requestLog, "id");
        return $requestLog;
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function updateLog(
        AbstractAppEndpoint    $route,
        LogEntity              $requestLog,
        ?RequestSnapshot       $snapshot,
        ?RouteLogTraceProvider $traceProvider = null
    ): void
    {
        $requestLog->alerts = $snapshot ? $snapshot->alerts : 0;
        $requestLog->responseCode = $route->response()->getStatusCode();
        $requestLog->endOn = round(microtime(true), 4);
        $requestLog->flagSid = $requestLog->flagSid ?: $traceProvider?->getTraceSid();
        $requestLog->flagUid = $requestLog->flagUid ?: $traceProvider?->getTraceUid();
        $requestLog->flagTid = $requestLog->flagTid ?: $traceProvider?->getTraceTid();
        $requestLog->snapshot = null;
        if ($snapshot) {
            if ($snapshot->alerts > 0 || $route->requestLogLevel->value >= 2) {
                $requestLog->snapshot = new Buffer(serialize($snapshot));
            }
        }

        $this->dbUpdateEntity(
            $requestLog,
            new StringVector("responseCode", "endOn", "errorCount", "flagSid", "flagUid", "flagTid", "snapshot"),
            $requestLog->id,
            "id"
        );
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @api
     */
    public function getLog(int $id): LogEntity
    {
        /** @var LogEntity */
        return $this->getFromDbColumn("id", $id);
    }
}