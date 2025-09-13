<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

require_once "bootstrap.php";
charcoal_autoloader();

use App\Shared\CharcoalApp;
use App\Shared\Constants\AppConstants;
use App\Shared\Enums\Interfaces;
use App\Shared\ErrorBoundary;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Internal\Exceptions\AppCrashException;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Http\Server\Exceptions\Internal\RequestGatewayException;
use Charcoal\Http\Server\HttpServer;
use Charcoal\Http\Server\Support\SapiRequest;

ErrorBoundary::configStreams(true, false, strlen(charcoal_from_root()))
    ::handle(function (\Throwable $e) {
        $exception = match (true) {
            $e instanceof AppCrashException,
                $e instanceof WrappedException,
                $e instanceof RequestGatewayException => match (true) {
                $e->getPrevious() instanceof \Throwable => $e->getPrevious(),
                default => $e,
            },
            default => $e
        };

        ErrorBoundary::crashHtmlPage($exception, charcoal_from_root(AppConstants::HTTP_CRASH_TEMPLATE));
        exit(1);
    });

$appFqcn = CharcoalApp::getAppFqcn();
$rootDirectory = (new DirectoryPath(charcoal_from_root()))->node();
$timestamp = MonotonicTimestamp::now();

/** @var CharcoalApp $charcoal */
//$charcoal = new $appFqcn(AppEnv::tryFrom(getenv("APP_ENV") ?: "dev"), $rootDirectory, null);
$charcoal = $appFqcn::Load(
    AppEnv::tryFrom(getenv("APP_ENV") ?: "dev"),
    $rootDirectory,
    ["var", "shared"]
);

$web = $charcoal->bootstrap($timestamp, Interfaces::Web);
assert($web instanceof HttpServer);

if ($charcoal->context->env !== AppEnv::Prod) {
    HttpServer::$enableOutputBuffering = true;
    HttpServer::$outputBufferToStdErr = true;
}

SapiRequest::serveResult($web->handle(SapiRequest::fromGlobals()));

