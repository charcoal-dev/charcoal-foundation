<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace Charcoal\Tests\Foundation;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Filesystem\Path\DirectoryPath;

/**
 * Class UnserializeTest
 * @package Charcoal\Tests\Foundation
 */
class UnserializeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @noinspection PhpExceptionImmediatelyRethrownInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUnserializeApp()
    {
        $rootDirectory = (new DirectoryPath(__DIR__ . "/build"))->node();

        fwrite(STDERR, "\033[35mInitializing...\n");

        try {
            $timestamp = MonotonicTimestamp::now();
            $charcoal = CharcoalApp::Load(AppEnv::Test, $rootDirectory, ["var", "shared"]);
        } catch (\Throwable $t) {
            throw $t;
        }

        $this->assertInstanceOf(CharcoalApp::class, $charcoal);
        fwrite(STDERR, "\033[40m\033[33mCharcoal App Initialized\033[0m\n");
        $charcoal->bootstrap($timestamp);
        $startupTime = $charcoal->diagnostics->startupTime / 1e6;
        fwrite(STDERR, "\033[33mStartup Time: \033[32m" . $startupTime . "ms\033[0m\n");

        $this->assertNotSame($charcoal->coreData, $charcoal->http,
            "CoreDataModule and HttpModule are distinct instances");
        $this->assertNotSame($charcoal->coreData, $charcoal->engine,
            "CoreDataModule and EngineModule are distinct instances");
        $this->assertNotSame($charcoal->coreData, $charcoal->mailer,
            "CoreDataModule and MailerModule are distinct instances");
        $this->assertNotSame($charcoal->http, $charcoal->engine,
            "HttpModule and EngineModule are distinct instances");
        $this->assertNotSame($charcoal->http, $charcoal->mailer,
            "HttpModule and MailerModule are distinct instances");
        $this->assertNotSame($charcoal->engine, $charcoal->mailer,
            "EngineModule and MailerModule are distinct instances");

        $this->assertSame($charcoal, $charcoal->coreData->app,
            "CoreDataModule back-reference to app is correct");
        $this->assertSame($charcoal, $charcoal->http->app,
            "HttpModule back-reference to app is correct");
        $this->assertSame($charcoal, $charcoal->mailer->app,
            "MailerModule back-reference to app is correct");
        $this->assertSame($charcoal, $charcoal->engine->app,
            "EngineModule back-reference to app is correct");

        $this->assertInstanceOf(\App\Shared\Foundation\Http\CallLog\CallLogHandler::class,
            $charcoal->http->callLog, "HttpModule has CallLogHandler");
        $this->assertInstanceOf(\App\Shared\Foundation\Http\InterfaceLog\LogHandler::class,
            $charcoal->http->interfaceLog, "HttpModule has Interface LogHandler");
        $this->assertInstanceOf(\App\Shared\Foundation\Http\ProxyServers\ProxiesHandler::class,
            $charcoal->http->proxies, "HttpModule has ProxiesHandler");
        $this->assertNotSame($charcoal->http->callLog, $charcoal->http->interfaceLog,
            "Http handlers are distinct");
        $this->assertNotSame($charcoal->http->callLog, $charcoal->http->proxies,
            "CallLogHandler and ProxiesHandler are distinct");
        $this->assertNotSame($charcoal->http->interfaceLog, $charcoal->http->proxies,
            "Interface LogHandler and ProxiesHandler are distinct");

        $this->assertInstanceOf(\App\Shared\Foundation\CoreData\ObjectStore\ObjectStoreService::class,
            $charcoal->coreData->objectStore, "CoreData has ObjectStoreService");
        $this->assertInstanceOf(\App\Shared\Foundation\CoreData\Countries\CountriesRepository::class,
            $charcoal->coreData->countries, "CoreData has CountriesRepository");
        $this->assertInstanceOf(\App\Shared\Foundation\CoreData\BruteForceControl\BruteForceLogger::class,
            $charcoal->coreData->bruteForce, "CoreData has BruteForceLogger");
        $this->assertInstanceOf(\App\Shared\Foundation\CoreData\DbBackups\DbBackupService::class,
            $charcoal->coreData->dbBackups, "CoreData has DbBackupService");
        $this->assertNotSame($charcoal->coreData->objectStore, $charcoal->coreData->countries,
            "CoreData components are distinct (objectStore vs countries)");
        $this->assertNotSame($charcoal->coreData->objectStore, $charcoal->coreData->bruteForce,
            "CoreData components are distinct (objectStore vs bruteForce)");
        $this->assertNotSame($charcoal->coreData->objectStore, $charcoal->coreData->dbBackups,
            "CoreData components are distinct (objectStore vs dbBackups)");
        $this->assertNotSame($charcoal->coreData->countries, $charcoal->coreData->bruteForce,
            "CoreData components are distinct (countries vs bruteForce)");
        $this->assertNotSame($charcoal->coreData->countries, $charcoal->coreData->dbBackups,
            "CoreData components are distinct (countries vs dbBackups)");
        $this->assertNotSame($charcoal->coreData->bruteForce, $charcoal->coreData->dbBackups
            , "CoreData components are distinct (bruteForce vs dbBackups)");

        $this->assertInstanceOf(\App\Shared\Foundation\Engine\Logs\LogService::class,
            $charcoal->engine->executionLog, "Engine has LogService");
        $this->assertInstanceOf(\App\Shared\Foundation\Engine\Metrics\MetricsLogger::class,
            $charcoal->engine->logStats, "Engine has MetricsLogger");
        $this->assertNotSame($charcoal->engine->executionLog, $charcoal->engine->logStats, "Engine components are distinct (executionLog vs logStats)");

        $client1 = $charcoal->http->client();
        $client2 = $charcoal->http->client();
        $this->assertSame($client1, $client2, "HttpService is memoized (single instance per HttpModule)");

        $httpStore1 = $charcoal->http->getCacheStore();
        $httpStore2 = $charcoal->http->getCacheStore();
        $this->assertTrue($httpStore1 === null || $httpStore1 === $httpStore2,
            "HttpModule cache store is stable (singleton when present)");

        $coreStore1 = $charcoal->coreData->getCacheStore();
        $coreStore2 = $charcoal->coreData->getCacheStore();
        $this->assertTrue($coreStore1 === null || $coreStore1 === $coreStore2,
            "CoreDataModule cache store is stable (singleton when present)");

        $engineStore1 = $charcoal->engine->getCacheStore();
        $engineStore2 = $charcoal->engine->getCacheStore();
        $this->assertTrue($engineStore1 === null || $engineStore1 === $engineStore2,
            "EngineModule cache store is stable (singleton when present)");

        $this->assertGreaterThan(0, $charcoal->diagnostics->startupTime, "Diagnostics startup time recorded (> 0)");
    }
}