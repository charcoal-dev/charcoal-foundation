<?php
declare(strict_types=1);

/** @noinspection PhpIncludeInspection */
require "vendor/autoload.php";

try {
    /** @var \App\Shared\CharcoalApp|string $appClass */
    $appClass = \App\Shared\CharcoalApp::getAppClassname();
    $appClassname = \Charcoal\OOP\OOP::baseClassName($appClass);
    $rootDirectory = new \Charcoal\Filesystem\Directory(__DIR__);
    $scriptDirectory = $rootDirectory->getDirectory("engine/scripts");

    $startOn = microtime(true);
    $arguments = explode(";", substr($argv[1] ?? "", 1, -1));
    $scriptName = $arguments[0] ?? null;

    if ($scriptName === "app_daemon") {
        $app = new $appClass($rootDirectory);
    } else {
        $app = $appClass::Load($rootDirectory, \App\Shared\BuildContext::GLOBAL, ["tmp"]);
    }

    $app->lifecycle->startedOn = $startOn;
    $app->bootstrap(); # Bootstrap all loaded modules & services
    $bootstrappedOn = microtime(true);

    $cli = new \Charcoal\App\Kernel\Interfaces\Cli\AppCliHandler(
        $app,
        $scriptDirectory,
        $arguments
    );

    $cli->print(sprintf("{grey}%s app bootstrapped in {green}%ss{/}", $appClassname, number_format($bootstrappedOn - $startOn, 4)));
    $cli->burn();
} catch (Throwable $t) {
    /** @noinspection PhpUnhandledExceptionInspection */
    throw $t;
}