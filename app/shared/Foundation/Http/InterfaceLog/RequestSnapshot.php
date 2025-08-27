<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http\InterfaceLog;

use App\Shared\CharcoalApp;
use App\Shared\Enums\Http\HttpLogLevel;
use App\Shared\Utility\ArrayHelper;
use Charcoal\App\Kernel\Diagnostics\ExecutionSnapshot;
use Charcoal\Database\Queries\ExecutedQuery;
use Charcoal\Database\Queries\FailedQuery;
use Charcoal\Http\Router\Contracts\Response\ResponseResolvedInterface;
use Charcoal\Http\Router\Controller\Promise\FileDownload;
use Charcoal\Http\Router\Request\Request;
use Charcoal\Http\Router\Response\AbstractResponse;
use Charcoal\Http\Router\Response\PayloadResponse;

/**
 * Class RequestSnapshot
 * @package App\Shared\Foundation\Http\InterfaceLog
 */
class RequestSnapshot
{
    public array $requestUrl;
    public array $requestHeaders = [];
    public array $requestParams = [];
    public array $responseHeaders = [];
    public array $responseParams = [];
    public ?string $responseFileDownload = null;
    public array $dbQueries = [];
    public ?ExecutionSnapshot $execution = null;
    public int $alerts = 0;
    public ?int $logLevelInitial = null;
    public ?int $logLevelFinal = null;

    /**
     * @param HttpLogLevel $logLevel
     * @param Request $request
     * @param array $excludeHeaders
     * @param array $excludeParams
     */
    public function __construct(
        HttpLogLevel $logLevel,
        Request      $request,
        array        $excludeHeaders = [],
        array        $excludeParams = []
    )
    {
        $this->logLevelInitial = $logLevel->value;
        $this->requestUrl = [
            "queryStr" => $request->url->query,
            "fragment" => $request->url->fragment
        ];

        // Initial Parameters
        if ($logLevel->value >= 2) {
            // Headers
            $this->requestHeaders = ArrayHelper::excludeKeys($request->headers->getArray(), $excludeHeaders);

            if ($logLevel === HttpLogLevel::Complete) {
                $this->requestParams = ArrayHelper::excludeKeys($request->payload->getArray(), $excludeParams);
            }
        }
    }

    /**
     * @param CharcoalApp $app
     * @param HttpLogLevel $logLevel
     * @param AbstractResponse $response
     * @param ResponseResolvedInterface $finalized
     * @param array $ignoreHeaders
     * @param array $ignoreParams
     * @return void
     */
    public function finalize(
        CharcoalApp               $app,
        HttpLogLevel              $logLevel,
        AbstractResponse          $response,
        ResponseResolvedInterface $finalized,
        array                     $ignoreHeaders = [],
        array                     $ignoreParams = []
    ): void
    {
        $this->logLevelFinal = $logLevel->value;

        if ($logLevel->value >= 2) {
            // Response Headers
            $this->responseHeaders = ArrayHelper::excludeKeys($response->headers->getArray(), $ignoreHeaders);
            if ($finalized instanceof FileDownload) {
                $this->responseFileDownload = $finalized->filepath;
            }

            if ($logLevel === HttpLogLevel::Complete) {
                // Response Payload
                if ($response instanceof PayloadResponse) {
                    $this->responseParams = ArrayHelper::excludeKeys($response->payload->getArray(), $ignoreParams);
                }
            }
        }

        // Diagnostics ExecutionSnapshot
        $snapshot = $app->diagnostics->snapshot(metrics: true, clean: true);
        $this->alerts = array_sum(array_slice(array_values($snapshot->alerts), 2));

        // Has errorCount > 0 OR HttpLogLevel === Complete
        if ($logLevel === HttpLogLevel::Complete || $this->alerts > 0) {
            // Database Queries
            $appDbQueries = $app->database->queriesAggregate();
            foreach ($appDbQueries as $dbQuery) {
                /** @var ExecutedQuery|FailedQuery $executed */
                $executed = $dbQuery["query"];
                $thisQuery = [
                    "db" => $dbQuery["db"],
                    "query" => [
                        "sql" => $executed->queryStr,
                        "data" => json_encode($executed->boundData),
                    ]
                ];

                if ($executed instanceof ExecutedQuery) {
                    $thisQuery["rowsCount"] = $executed->rowsCount;
                }

                if ($executed instanceof FailedQuery) {
                    $thisQuery["error"] = [
                        "code" => $executed->error->code,
                        "info" => $executed->error->info,
                        "sqlState" => $executed->error->sqlState
                    ];
                }

                $this->dbQueries[] = $thisQuery;
                unset($thisQuery);
            }
        }
    }
}