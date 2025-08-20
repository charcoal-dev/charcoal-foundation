<?php
declare(strict_types=1);

namespace Charcoal\Tests\Foundation;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Diagnostics\Events\BuildStageEvents;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Filesystem\Path\DirectoryPath;
use PHPUnit\Framework\TestCase;

/**
 * Class CharcoalAppConstructTest
 * @package Charcoal\Tests\Foundation
 */
class CharcoalAppConstructTest extends TestCase
{
    /**
     * @noinspection PhpExceptionImmediatelyRethrownInspection
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCharcoalInitializerWithMonitor()
    {
        fwrite(STDERR, "\033[35mInitializing...\n");
        fwrite(STDERR, "\033[40m\033[33mCharcoal App\033[0m\n");

        try {
            $timestamp = MonotonicTimestamp::now();
            $charcoal = new CharcoalApp(
                AppEnv::Test,
                (new DirectoryPath(__DIR__ . "/build"))->node(),
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

        // Check Diagnostics Cleanup of BuildStageEvents
        fwrite(STDERR, "\033[33mDiagnostics Snapshot: \033[32mOK\033[0m\n");
    }
}