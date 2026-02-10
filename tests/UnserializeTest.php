<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace Charcoal\Tests\Foundation;

use App\Shared\CharcoalApp;
use App\Shared\CoreData\Bfc\BfcRepository;
use App\Shared\CoreData\Countries\CountriesRepository;
use App\Shared\CoreData\ObjectStore\ObjectStoreRepository;
use App\Shared\Telemetry\AppLogs\AppLogsRepository;
use App\Shared\Telemetry\HttpIngress\HttpIngressRepository;
use App\Shared\Telemetry\Metrics\MetricsRepository;
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
        $rootDirectory = (new DirectoryPath(realpath(__DIR__ . "/../")))->node();

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

        $this->assertNotSame($charcoal->coreData, $charcoal->mailer,
            "CoreDataModule and MailerModule are distinct instances");
        $this->assertNotSame($charcoal->telemetry, $charcoal->mailer,
            "TelemetryModule and MailerModule are distinct instances");

        $this->assertSame($charcoal, $charcoal->coreData->app,
            "CoreDataModule back-reference to app is correct");
        $this->assertSame($charcoal, $charcoal->telemetry->app,
            "TelemetryModule back-reference to app is correct");
        $this->assertSame($charcoal, $charcoal->mailer->app,
            "MailerModule back-reference to app is correct");

        $this->assertInstanceOf(AppLogsRepository::class,
            $charcoal->telemetry->appLogs, "TelemetryModule has AppLogsRepository");
        $this->assertInstanceOf(MetricsRepository::class,
            $charcoal->telemetry->metrics, "TelemetryModule has Interface MetricsRepository");
        $this->assertInstanceOf(HttpIngressRepository::class,
            $charcoal->telemetry->httpIngress, "TelemetryModule has HttpIngressRepository");

        $this->assertInstanceOf(ObjectStoreRepository::class,
            $charcoal->coreData->objectStore, "CoreData has ObjectStoreRepository");
        $this->assertInstanceOf(CountriesRepository::class,
            $charcoal->coreData->countries, "CoreData has CountriesRepository");
        $this->assertInstanceOf(BfcRepository::class,
            $charcoal->coreData->bfc, "CoreData has BruteForceLogger");
        $this->assertNotSame($charcoal->coreData->objectStore, $charcoal->coreData->countries,
            "CoreData components are distinct (objectStore vs countries)");
        $this->assertNotSame($charcoal->coreData->objectStore, $charcoal->coreData->bfc,
            "CoreData components are distinct (objectStore vs bruteForce)");
        $this->assertNotSame($charcoal->coreData->objectStore, $charcoal->coreData->bfc,
            "CoreData components are distinct (objectStore vs dbBackups)");
        $this->assertNotSame($charcoal->coreData->countries, $charcoal->coreData->bfc,
            "CoreData components are distinct (countries vs bruteForce)");

        $this->assertGreaterThan(0, $charcoal->diagnostics->startupTime, "Diagnostics startup time recorded (> 0)");
    }
}