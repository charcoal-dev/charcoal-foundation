<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use App\Shared\Foundation\Http\HttpLogLevel;
use Charcoal\HTTP\Client\Request;

/**
 * Class CallLogSnapshot
 * @package App\Shared\Foundation\Http\CallLog
 */
class CallLogSnapshot
{
    public readonly int $callId;
    public readonly array $requestUrl;
    public readonly array $requestHeaders;
    public ?array $requestPayload = null;
    public ?string $requestBody = null;
    public ?array $exception = null;
    public array $responseHeaders;
    public ?array $responsePayload = null;
    public ?string $responseBody = null;

    /**
     * @param CallLogEntity $log
     * @param Request $request
     * @param HttpLogLevel $logLevel
     */
    public function __construct(CallLogEntity $log, Request $request, HttpLogLevel $logLevel)
    {
        $this->callId = $log->id;
        $this->requestUrl = [
            "queryStr" => $request->url->query,
            "fragment" => $request->url->fragment
        ];

        $this->requestHeaders = $request->headers->toArray();
        if ($logLevel === HttpLogLevel::COMPLETE) {
            if ($request->payload->count()) {
                $this->requestPayload = $request->payload->toArray();
            } else {
                $this->requestBody = $request->body->raw();
            }
        }
    }
}