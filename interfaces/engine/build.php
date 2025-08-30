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
use App\Shared\Core\ErrorBoundary;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Diagnostics\Events\BuildStageEvents;
use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Cli\Output\StdoutPrinter;
use Charcoal\Filesystem\Path\DirectoryPath;

ErrorBoundary::configStreams(true, false, strlen(charcoal_from_root()));

$stdout = new StdoutPrinter();
$stdout->useAnsiCodes(true);
$appFqcn = \App\Shared\CharcoalApp::getAppFqcn();
$stdout->write("{yellow}" . ObjectHelper::baseClassName($appFqcn), true);
$stdout->write("{cyan}" . $appFqcn, true);
$stdout->write("Root Directory: ", false);
$rootDirectory = (new DirectoryPath(charcoal_from_root()))->node();
$stdout->write("{green}" . $rootDirectory->path->absolute, true);
$stdout->write("Shared Context Path: ", false);
$sharedContext = $rootDirectory->directory("var/shared", true, false);
$stdout->write("{green}" . $sharedContext->path->absolute, true);
$stdout->write("", true);

$test = new \Charcoal\App\Kernel\Support\Errors\FileErrorLogger($rootDirectory->childPathInfo("error.log")->absolute);

$timestamp = MonotonicTimestamp::now();
$charcoal = new CharcoalApp(
    \Charcoal\App\Kernel\Enums\AppEnv::tryFrom(getenv("APP_ENV") ?: "dev"),
    $rootDirectory,
    function (BuildStageEvents $events) use ($stdout) {
        $stdout->write("{cyan}Build Stage:{/} {yellow}" . $events->name . "{/}", true);
    }
);

$charcoal->bootstrap($timestamp);
$startupTime = $charcoal->diagnostics->startupTime / 1e6;
$stdout->write("", true);
$stdout->write("{magenta}" . ObjectHelper::baseClassName($appFqcn) . " Initialized", true);
$stdout->write("{cyan}Initialization Time: {green}" . $startupTime . "ms", true);
$build = CharcoalApp::CreateBuild($charcoal, $rootDirectory, ["tmp"]);
$stdout->write("{cyan}Snapshot Size: {green}" . round(filesize($build->absolute) / 1024, 2) . " KB", true);

