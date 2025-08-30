<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace Charcoal\Tests\Foundation;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Diagnostics\Events\BuildStageEvents;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Filesystem\Path\DirectoryPath;
use PHPUnit\Framework\TestCase;

/**
 * Class CharcoalAppConstructTest
 * @package Charcoal\Tests\Foundation
 */
class ConstructTest extends TestCase
{
    /**
     * @noinspection PhpExceptionImmediatelyRethrownInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCharcoalInitializerWithMonitor()
    {
        fwrite(STDERR, "\033[35mInitializing...\n");
        fwrite(STDERR, "\033[40m\033[33mCharcoal App\033[0m\n");

        $rootDirectory = (new DirectoryPath(__DIR__ . "/build"))->node();
        try {
            $timestamp = MonotonicTimestamp::now();
            $charcoal = new CharcoalApp(
                AppEnv::Dev,
                $rootDirectory,
                function (BuildStageEvents $events) {
                    fwrite(STDERR, "\033[36mBuild Stage:\033[0m \033[33m" . $events->name . "\033[0m\n");
                }
            );
        } catch (\Throwable $t) {
            throw $t;
        }

        fwrite(STDERR, "\033[35mCharcoal App Initialized\033[0m\n");

        // First assertion
        $this->assertInstanceOf(CharcoalApp::class, $charcoal);
        $charcoal->bootstrap($timestamp);
        $startupTime = $charcoal->diagnostics->startupTime / 1e6;
        fwrite(STDERR, "\033[33mStartup Time: \033[32m" . $startupTime . "ms\033[0m\n");

        // Verify that event subscriptions were dropped for BuiltStageEvents after bootstrap
        $eventInspect = $charcoal->diagnostics->eventInspection(false);
        $this->assertCount(0, $eventInspect->current[BuildStageEvents::class], "No subscribers held for "
            . ObjectHelper::baseClassName(BuildStageEvents::class));

        // 2 Subscribers = One for this test, closure passed to constructor is bound to the event subscription,
        // and the other one is the ErrorManager from Foundation app waiting for PathRegistry to resolve so it can
        // load template.
        $this->assertCount(2, $eventInspect->history[BuildStageEvents::class],
            "Total 2 overall subscribers");

        // Serialize the application
        CharcoalApp::CreateBuild($charcoal, $rootDirectory, ["tmp"]);
    }
}