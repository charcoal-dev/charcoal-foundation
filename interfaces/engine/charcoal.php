<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link         https://github.com/charcoal-dev/charcoal-foundation
 * @noinspection PhpUnhandledExceptionInspection
 */

declare(strict_types=1);

use App\Shared\AppConstants;
use App\Shared\CharcoalApp;
use App\Shared\Enums\Interfaces;
use App\Shared\ErrorBoundary;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Internal\Exceptions\AppCrashException;
use Charcoal\App\Kernel\ServerApi\Cli\AppCliHandler;
use Charcoal\App\Kernel\ServerApi\Cli\ConsoleErrorWriter;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Filesystem\Path\DirectoryPath;

require_once "bootstrap.php";
charcoal_autoloader();

$appFqcn = CharcoalApp::getAppFqcn();
$rootDirectory = new DirectoryPath(charcoal_from_root())->node();
ErrorBoundary::configStreams(true, false, strlen(charcoal_from_root()))::handle(function (\Throwable $e) {
    new ConsoleErrorWriter(AppConstants::CONSOLE_ANSI, PHP_EOL)->handleException(
        $e instanceof AppCrashException ? $e->getPrevious() : $e,
    );

    exit(1);
});

charcoal_enforce_maintenance();

$timestamp = MonotonicTimestamp::now();

/** @var CharcoalApp $charcoal */
$charcoal = $appFqcn::Load(AppEnv::tryFrom(getenv("APP_ENV") ?: "dev"), $rootDirectory, ["var", "shared"]);
$console = $charcoal->bootstrap($timestamp, Interfaces::Engine);
/** @var AppCliHandler $console */

$startupTime = $charcoal->diagnostics->startupTime / 1e6;
$charcoal->errors->debugBacktraceOffset(0);
$console->stdout->useAnsiCodes(true);

$console->print(sprintf("{grey}%s app bootstrapped in {green}%sms{/}",
    ObjectHelper::baseClassName($appFqcn), $startupTime));
$console->exec();
