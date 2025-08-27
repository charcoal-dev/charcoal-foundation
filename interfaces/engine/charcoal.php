<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

/** @noinspection PhpIncludeInspection */
require "vendor/autoload.php";

try {
    /** @var \App\Shared\CharcoalApp|string $appClass */
    $appClass = \App\Shared\CharcoalApp::getAppClassname();
    $appClassname = \Charcoal\OOP\OOP::baseClassName($appClass);
    $rootDirectory = new \Charcoal\Filesystem\Directory(__DIR__);

    $startOn = microtime(true);
    $arguments = explode(";", substr($argv[1] ?? "", 1, -1));
    $scriptName = $arguments[0] ?? null;

    $app = $appClass::Load($rootDirectory, \App\Shared\Context\BuildContext::GLOBAL, ["tmp"]);
    //$app = new $appClass($rootDirectory);

    $app->lifecycle->startedOn = $startOn;
    $app->bootstrap(); # Bootstrap all loaded modules & services
    $bootstrappedOn = microtime(true);

    $cli = new \Charcoal\App\Kernel\Interfaces\Cli\AppCliHandler(
        $app, 'App\Interfaces\Engine\Scripts', $arguments, defaultScriptName: "fallback"
    );

    $cli->print(sprintf("{grey}%s app bootstrapped in {green}%ss{/}", $appClassname, number_format($bootstrappedOn - $startOn, 4)));
    $cli->burn();
} catch (Throwable $t) {
    /** @noinspection PhpUnhandledExceptionInspection */
    throw $t;
}