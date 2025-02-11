<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Foundation\Http\HttpInterface;
use App\Shared\Foundation\Http\HttpLogLevel;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogEntity;
use App\Shared\Foundation\Http\InterfaceLog\InterfaceLogSnapshot;
use App\Shared\Utility\NetworkValidator;
use Charcoal\App\Kernel\Errors;
use Charcoal\App\Kernel\Interfaces\Http\AbstractEndpoint;
use Charcoal\HTTP\Commons\HttpMethod;

/**
 * Class AppAwareEndpoint
 * @package App\Shared\Core\Http
 * @property CharcoalApp $app
 */
abstract class AppAwareEndpoint extends AbstractEndpoint
{
    protected const array LOG_IGNORE_REQUEST_HEADERS = [];
    protected const array LOG_IGNORE_RESPONSE_HEADERS = [];
    protected const array LOG_IGNORE_REQUEST_PARAMS = [];
    protected const array LOG_IGNORE_RESPONSE_PARAMS = [];

    public readonly string $userIpAddress;
    public readonly ?HttpInterfaceBinding $interface;

    public readonly HttpLogLevel $requestLogLevel;
    private readonly ?InterfaceLogEntity $requestLog;
    private readonly ?InterfaceLogSnapshot $requestLogSnapshot;

    /**
     * @return void
     */
    final protected function dispatchEntrypoint(): void
    {
        // User IP Address
        $this->userIpAddress = $this->userClient->cfConnectingIP ??
            $this->userClient->xForwardedFor ??
            $this->userClient->ipAddress;

        if (!NetworkValidator::isValidIpAddress($this->userIpAddress, true, true)) {
            throw new \UnexpectedValueException("Invalid remote IP address");
        }

        // Interface Configuration
        $this->interface = $this->declareHttpInterface();

        // Proceed to entrypoint
        parent::dispatchEntrypoint();
    }

    /**
     * @return void
     */
    abstract public function appAwareCallback(): void;

    /**
     * @return HttpInterface|null
     */
    abstract protected function declareHttpInterface(): ?HttpInterfaceBinding;

    /**
     * @return HttpLogLevel
     */
    protected function declareLogLevel(): HttpLogLevel
    {
        return HttpLogLevel::NONE;
    }

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    final protected function beforeEntrypointCallback(): void
    {
        // Interface Status
        if ($this->interface && !$this->interface->config->status) {
            throw new \RuntimeException(
                sprintf('HTTP Interface "%s" is DISABLED', $this->interface->enum->name)
            );
        }

        // InterfaceLog
        $routeLogLevel = $this->declareLogLevel();
        $configLogLevel = $this->interface ? $this->interface->config->logData : HttpLogLevel::NONE;
        if ($this->request->method === HttpMethod::OPTIONS) {
            if (!$this->interface?->config?->logHttpMethodOptions) {
                $configLogLevel = HttpLogLevel::NONE;
                $routeLogLevel = HttpLogLevel::NONE;
            }
        }

        $this->requestLogLevel = HttpLogLevel::from(max($routeLogLevel->value, $configLogLevel->value));
        if ($this->requestLogLevel->value === 0) {
            $this->requestLog = null;
            $this->requestLogSnapshot = null;
        } else {
            if (!$this->interface) {
                throw new \RuntimeException('Cannot initialize InterfaceLog without HTTP Interface declaration');
            }

            if (!isset($this->app->http->interfaceLog)) {
                throw new \RuntimeException("HTTP module does not have InterfaceLog component built");
            }

            $this->requestLogSnapshot = $this->requestLogLevel->value >= 2 ? new InterfaceLogSnapshot(
                $this->requestLogLevel,
                $this->request,
                static::LOG_IGNORE_REQUEST_HEADERS,
                static::LOG_IGNORE_REQUEST_PARAMS,
            ) : null;

            $this->requestLog = $this->app->http->interfaceLog->createLog($this, $this->requestLogSnapshot);
        }

        // Callback
        $this->appAwareCallback();
    }

    /**
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\HTTP\Router\Exception\RouterException
     */
    public function sendResponse(): void
    {
        if ($this->requestLog) {
            try {
                $this->requestLogSnapshot?->finalise(
                    $this->app,
                    $this->requestLogLevel,
                    $this->response,
                    static::LOG_IGNORE_RESPONSE_HEADERS,
                    static::LOG_IGNORE_RESPONSE_PARAMS
                );

                $this->app->http->interfaceLog->updateLog($this, $this->requestLog, $this->requestLogSnapshot);
            } catch (\Throwable $t) {
                $this->writeLogDumpToFile($t);
            }
        }

        parent::sendResponse();
    }

    /**
     * @param \Throwable $t
     * @return void
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    private function writeLogDumpToFile(\Throwable $t): void
    {
        $logFileDump = [
            "exception" => Errors::Exception2Array($t),
            "errors" => $this->app->errors->getAll(),
            "lifecycle" => $this->app->lifecycle->getAll(),
        ];

        $this->app->directories->log->getDirectory("queries", true)
            ->writeToFile(dechex($this->requestLog->id), var_export($logFileDump, true));
    }
}