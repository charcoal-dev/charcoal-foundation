<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\Http\HttpLogLevel;
use App\Shared\Foundation\Http\HttpModule;
use App\Shared\Foundation\Http\ProxyServers\HttpProxy;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Repository\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\EntityUpdatableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Client\Request;
use Charcoal\Http\Client\Response;
use Charcoal\OOP\Vectors\DsvString;
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class CallLogHandler
 * @package App\Shared\Foundation\Http\CallLog
 */
class CallLogHandler extends AbstractOrmRepository
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    /**
     * @param HttpModule $module
     */
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, AppDbTables::HTTP_CALL_LOG);
    }

    /**
     * @param Request $request
     * @param HttpProxy|null $proxyServer
     * @param DsvString|null $flags
     * @return CallLogEntity
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function createLog(
        Request    $request,
        ?HttpProxy $proxyServer,
        ?DsvString $flags,
    ): CallLogEntity
    {
        $callLog = new CallLogEntity();
        $callLog->proxyId = $proxyServer?->uniqId;
        $callLog->flags = $flags;
        $callLog->method = $request->method;
        $callLog->urlServer = $request->url->scheme . "://" . $request->url->host;
        if ($request->url->port) {
            $callLog->urlServer .= ":" . $request->url->port;
        }

        $callLog->urlPath = $request->url->path ?? "/";
        $callLog->startOn = round(microtime(true), 4);
        $callLog->endOn = null;
        $callLog->responseCode = null;
        $callLog->responseLength = null;
        $callLog->snapshot = null;

        $this->dbInsertAndSetId($callLog, "id");
        return $callLog;
    }

    /**
     * @param CallLogEntity $callLog
     * @param CallLogSnapshot $snapshot
     * @param Response|null $response
     * @param float $timestamp
     * @param HttpLogLevel $logLevel
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function finaliseCallLog(
        CallLogEntity   $callLog,
        CallLogSnapshot $snapshot,
        ?Response       $response,
        float           $timestamp,
        HttpLogLevel    $logLevel,
    ): void
    {
        $callLog->endOn = round($timestamp, 4);
        if ($response) {
            $callLog->responseCode = $response->statusCode;
            $callLog->responseLength = $response->body->len();
            $snapshot->responseHeaders = $response->headers->toArray();
            if ($logLevel === HttpLogLevel::COMPLETE) {
                if ($response->payload->count()) {
                    $snapshot->responsePayload = $response->payload->toArray();
                } else {
                    $snapshot->responseBody = $response->body?->raw();
                }
            }
        }

        if ($snapshot->exception || $logLevel->value >= HttpLogLevel::HEADERS->value) {
            $callLog->snapshot = new Buffer(serialize($snapshot));
        }

        $this->dbUpdateEntity(
            $callLog,
            new StringVector("endOn", "responseCode", "responseLength", "snapshot"),
            $callLog->id,
            "id"
        );
    }
}