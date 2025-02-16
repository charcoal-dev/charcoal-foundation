<?php
declare(strict_types=1);

/** @noinspection PhpIncludeInspection */
require "../vendor/autoload.php";

try {
    // Instantiate router & define routes
    $router = new Charcoal\Http\Router\Router();
    $router->route('/*', 'App\Interfaces\Web\Endpoints\*')
        ->fallbackController(\App\Interfaces\Web\FallbackEndpoint::class);

    // Instantiate application
    $rootDirectory = new \Charcoal\Filesystem\Directory(dirname(__FILE__, 2));

    /** @var \App\Shared\CharcoalApp|string $appClass */
    $appClass = \App\Shared\CharcoalApp::getAppClassname();

    // Bootstrap App
    $startOn = microtime(true);
    // $app = new $appClass($rootDirectory);
    $app = $appClass::Load($rootDirectory, \App\Shared\BuildContext::GLOBAL, ["tmp"]);
    $app->lifecycle->startedOn = $startOn;
    $app->bootstrap(); # Bootstrap all loaded modules & services
    $bootstrappedOn = microtime(true);

    // Controllers Arguments
    $router->setControllersArgs([$app, \Charcoal\App\Kernel\Interfaces\Http\RemoteClient::class]);

    // Create request from _SERVER globals
    \Charcoal\Http\Router\HttpServer::requestFromServerGlobals($router,
        function (\App\Interfaces\Web\AbstractWebEndpoint $endpoint) {
            $endpoint->sendResponse();
        });
} catch (Throwable $t) {
    /** @noinspection PhpUnhandledExceptionInspection */
    throw $t;
}