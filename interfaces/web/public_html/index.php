<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

require_once "bootstrap.php";
charcoal_autoloader();

use App\Shared\CharcoalApp;
use App\Shared\Constants\AppConstants;
use App\Shared\Core\ErrorBoundary;
use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Internal\Exceptions\AppCrashException;
use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Http\Server\HttpServer;
use Charcoal\Http\Server\Support\SapiRequest;

ErrorBoundary::configStreams(true, false, strlen(charcoal_from_root()))
    ::handle(function (\Throwable $e) {
        ErrorBoundary::crashHtmlPage($e instanceof AppCrashException ?
            $e->getPrevious() : $e,
            charcoal_from_root(AppConstants::HTTP_CRASH_TEMPLATE));
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
$response = $web->handle(SapiRequest::fromGlobals());

var_dump($response);




