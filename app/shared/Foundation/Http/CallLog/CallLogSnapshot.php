<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use App\Shared\Enums\Http\HttpLogLevel;
use Charcoal\Http\Client\Request;

/**
 * This class provides a structured representation of a call log, including request URLs,
 * headers, payloads, and response data. It supports optional request bodies
 * and exceptions for error scenarios.
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

        $this->requestHeaders = $request->headers->getArray();
        if ($logLevel === HttpLogLevel::Complete) {
            if ($request->payload->count()) {
                $this->requestPayload = $request->payload->getArray();
            } else {
                $this->requestBody = $request->body->raw();
            }
        }
    }
}