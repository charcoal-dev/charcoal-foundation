<?php
declare(strict_types=1);

/** @noinspection PhpIncludeInspection */
require "vendor/autoload.php";

$stdout = new \Charcoal\CLI\Console\StdoutPrinter();
$stdout->useAnsiCodes(true);

try {
    $appClass = \App\Shared\CharcoalApp::getAppClassname();
    $appClassname = \Charcoal\OOP\OOP::baseClassName($appClass);
    $rootDirectory = new \Charcoal\Filesystem\Directory(__DIR__);

    $stdout->write(sprintf("Creating {invert}{yellow} %s {/} build... ", $appClassname), false);
    $startOn = microtime(true);
    /** @var \App\Shared\CharcoalApp|string $app */
    $app = new $appClass(\App\Shared\BuildContext::GLOBAL, $rootDirectory);
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