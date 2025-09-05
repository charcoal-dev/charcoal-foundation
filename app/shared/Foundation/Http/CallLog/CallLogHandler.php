<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Http\HttpLogLevel;
use App\Shared\Foundation\Http\HttpModule;
use App\Shared\Foundation\Http\ProxyServers\ProxyServer;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityUpdatableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Client\Request;
use Charcoal\Http\Client\Response;
use Charcoal\Vectors\Strings\StringVector;
use Charcoal\Vectors\Support\DsvTokens;

/**
 * Handles the lifecycle of HTTP call logs, including their creation, updates, and persistence.
 * @property HttpModule $module
 */
final class CallLogHandler extends OrmRepositoryBase
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    /**
     * @param HttpModule $module
     */
    public function __construct(HttpModule $module)
    {
        parent::__construct($module, DatabaseTables::HttpCallLog);
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function createLog(
        Request      $request,
        ?ProxyServer $proxyServer,
        ?DsvTokens   $flags,
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
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function finalizeCallLog(
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
            $callLog->responseLength = $response->body->length();
            $snapshot->responseHeaders = $response->headers->getArray();
            if ($logLevel === HttpLogLevel::Complete) {
                if ($response->payload->count()) {
                    $snapshot->responsePayload = $response->payload->getArray();
                } else {
                    $snapshot->responseBody = $response->body?->bytes();
                }
            }
        }

        if ($snapshot->exception || $logLevel->value >= HttpLogLevel::Headers->value) {
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