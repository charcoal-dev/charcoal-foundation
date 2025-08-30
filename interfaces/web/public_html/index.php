<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

use App\Shared\CharcoalApp;
use App\Shared\Constants\AppConstants;
use App\Shared\Core\ErrorBoundary;
use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Filesystem\Path\DirectoryPath;

require_once "bootstrap.php";
charcoal_autoloader();

ErrorBoundary::configStreams(true, false, strlen(charcoal_from_root()))
    ::handle(function (\Throwable $e) {
        ErrorBoundary::crashHtmlPage($e, charcoal_from_root(AppConstants::CRASH_HTML_TEMPLATE));
    });

$appFqcn = CharcoalApp::getAppFqcn();
$rootDirectory = (new DirectoryPath(charcoal_from_root()))->node();
$timestamp = MonotonicTimestamp::now();

/** @var CharcoalApp $charcoal */
$charcoal = $appFqcn::Load(AppEnv::tryFrom(getenv("APP_ENV") ?: "dev"), $rootDirectory, ["var", "shared"]);
$charcoal->bootstrap($timestamp);
$startupTime = $charcoal->diagnostics->startupTime / 1e6;


$httpServer = new \Charcoal\Http\Server\HttpServer(
    $charcoal->config->sapi->interfaces[Interfaces::Web->name],

);