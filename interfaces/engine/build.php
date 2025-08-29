<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Diagnostics\Events\BuildStageEvents;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Filesystem\Path\DirectoryPath;

require "dev/composer/vendor/autoload.php";

$stdout = new \Charcoal\Cli\Output\StdoutPrinter();
$stdout->useAnsiCodes(true);

try {
    $stdout->write("{cyan}Initializing...", true);
    $appFqcn = \App\Shared\CharcoalApp::getAppFqcn();
    $appId = \Charcoal\Base\Support\Helpers\ObjectHelper::baseClassName($appFqcn);
    $stdout->write("{yellow}" . $appId . "{/}...", true);
    $stdout->write("", true);

    $stdout->write("{grey}Root Directory: {/}", false);
    $rootDirectory = (new DirectoryPath(dirname(__FILE__, 3)))->node();
    $stdout->write("{green}" . $rootDirectory->path->absolute, true);
    $stdout->write("{grey}Shared Context Path: {/}", false);
    $sharedContext = $rootDirectory->directory("tmp/shared", true, false);
    $stdout->write("{green}" . $sharedContext->path->absolute, true);

    exit;

    try {
        $timestamp = MonotonicTimestamp::now();
        $charcoal = new CharcoalApp(
            AppEnv::Test,
            $rootDirectory,
            function (BuildStageEvents $events) {
                fwrite(STDERR, "\033[36mBuild Stage:\033[0m \033[33m" . $events->name . "\033[0m\n");
            }
        );
    } catch (\Throwable $t) {
        throw $t;
    }

    fwrite(STDERR, "\033[35mCharcoal App Initialized\033[0m\n");


    $appClassname = \App\Shared\CharcoalApp::getAppClassname();
    $appClassname = \Charcoal\OOP\OOP::baseClassName($appClass);
    $rootDirectory = new \Charcoal\Filesystem\Directory(__DIR__);

    $stdout->write(sprintf("Creating {invert}{yellow} %s {/} build... ", $appClassname), false);
    $startOn = microtime(true);
    /** @var \App\Shared\CharcoalApp|string $app */
    $app = new $appClass(\App\Shared\Context\BuildContext::GLOBAL, $rootDirectory);
    $app->lifecycle->startedOn = $startOn;
    $app->bootstrap(); # Bootstrap all modules & services

    $stdout->write("{green}" . number_format(microtime(true) - $startOn, 4) . "s", true);
    $stdout->write("Writing to {cyan}tmp{/} directory... ", false);
    $app::CreateBuild($app, $app->directories->tmp);
    $stdout->write("{green}Success{/}", true);
    $stdout->write("{grey}Checking error handler... ", false);
    if ($app->errors->count() > 0) {
        $stdout->write(sprintf("{red}%d{/}", $app->errors->count()), true);
    } else {
        $stdout->write("{green}All good!{/}", true);
    }

} catch (Throwable $t) {
    $stdout->write("", true);
    $stdout->write(sprintf(
        "{red}{invert} %s {/}: {grey}#%s{/} {red}%s{/} {grey}near{/} {cyan}%s{/}{grey}:{/}{yellow}%d{/}",
        get_class($t),
        $t->getCode(),
        $t->getMessage(),
        $t->getFile(),
        $t->getLine(),
    ), true);
}

if (isset($app)) {
    if ($app->errors->count()) {
        $stdout->write("", true);
        $stdout->write("{red}Errors Caught:{/}", true);

        /** @var \Charcoal\App\Kernel\Errors\ErrorEntry $errorMsg */
        foreach ($app->errors as $errorMsg) {
            $stdout->write(json_encode($errorMsg, JSON_PRETTY_PRINT), true);
        }
    }
}