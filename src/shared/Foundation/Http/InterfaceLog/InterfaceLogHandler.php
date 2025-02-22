<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\InterfaceLog;

use App\Shared\AppDbTables;
use App\Shared\Core\Http\AppAwareEndpoint;
use App\Shared\Foundation\Http\HttpModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Exception\NoChangesException;
use Charcoal\App\Kernel\Orm\Repository\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\EntityUpdatableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class InterfaceLogHandler
 * @package App\Shared\Foundation\Http\InterfaceLog
 * @property HttpModule $module
 */
class InterfaceLogHandler extends AbstractOrmRepository
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    /**
     * @param HttpModule $module
     */
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, AppDbTables::HTTP_INTERFACE_LOG);
    }

    /**
     * @param AppAwareEndpoint $route
     * @param InterfaceLogSnapshot|null $snapshot
     * @return InterfaceLogEntity
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function createLog(AppAwareEndpoint $route, ?InterfaceLogSnapshot $snapshot): InterfaceLogEntity
    {
        $requestLog = new InterfaceLogEntity();
        $requestLog->interface = $route->interface->enum;
        $requestLog->ipAddress = $route->userIpAddress;
        $requestLog->method = $route->request->method;
        $requestLog->endpoint = $route->request->url->path ?? "/";
        $requestLog->startOn = round(microtime(true), 4);
        $requestLog->endOn = null;
        $requestLog->responseCode = null;
        $requestLog->flagSid = null;
        $requestLog->flagUid = null;
        $requestLog->snapshot = $snapshot ? new Buffer(serialize($snapshot)) : null;
        $this->dbInsertAndSetId($requestLog, "id");
        return $requestLog;
    }

    /**
     * @param AppAwareEndpoint $route
     * @param InterfaceLogEntity $requestLog
     * @param InterfaceLogSnapshot|null $snapshot
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function updateLog(
        AppAwareEndpoint      $route,
        InterfaceLogEntity    $requestLog,
        ?InterfaceLogSnapshot $snapshot
    ): void
    {
        $requestLog->responseCode = $route->response()->getStatusCode();
        $requestLog->endOn = round(microtime(true), 4);
        if ($route instanceof RouteLogTraceProvider) {
            $requestLog->flagSid = $route->getTraceSid();
            $requestLog->flagUid = $route->getTraceUid();
        }

        $requestLog->snapshot = $snapshot ? new Buffer(serialize($snapshot)) : null;

        try {
            $this->dbUpdateEntity(
                $requestLog,
                new StringVector("responseCode", "endOn", "flagSid", "flagUid", "snapshot"),
                $requestLog->id,
                "id"
            );
        } catch (NoChangesException) {
        }
    }
}