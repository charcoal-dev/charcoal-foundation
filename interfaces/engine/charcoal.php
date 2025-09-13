<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

use App\Shared\CharcoalApp;
use App\Shared\ErrorBoundary;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\ServerApi\Cli\AppCliHandler;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Filesystem\Path\DirectoryPath;

require_once "bootstrap.php";
charcoal_autoloader();

$appFqcn = CharcoalApp::getAppFqcn();
$rootDirectory = (new DirectoryPath(charcoal_from_root()))->node();
ErrorBoundary::configStreams(true, false, strlen(charcoal_from_root()));
$timestamp = MonotonicTimestamp::now();

/** @var CharcoalApp $charcoal */
$charcoal = $appFqcn::Load(AppEnv::tryFrom(getenv("APP_ENV") ?: "dev"), $rootDirectory, ["var", "shared"]);
$charcoal->bootstrap($timestamp);
$startupTime = $charcoal->diagnostics->startupTime / 1e6;
$charcoal->errors->debugBacktraceOffset(0);

if ($charcoal->context->env !== AppEnv::Prod) {
    $scriptInject = getenv("SAPI_ENGINE_SCRIPT_INJECT");
    if ($scriptInject) {
        $argv = explode("|", $scriptInject);
    }
}

$console = new AppCliHandler($charcoal,
    "App\\Sapi\\Engine\\Scripts",
    explode(";", substr($argv[1] ?? "", 1, -1)),
    "fallback"
);

$console->stdout->useAnsiCodes(true);

$console->print(sprintf("{grey}%s app bootstrapped in {green}%ss{/}",
    ObjectHelper::baseClassName($appFqcn), $startupTime));
$console->exec();
