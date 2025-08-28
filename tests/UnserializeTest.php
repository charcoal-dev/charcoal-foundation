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
            $charcoal = CharcoalApp::Load(AppEnv::Test, $rootDirectory, ["tmp"]);
        } catch (\Throwable $t) {
            throw $t;
        }

        $this->assertInstanceOf(CharcoalApp::class, $charcoal);
        fwrite(STDERR, "\033[40m\033[33mCharcoal App Initialized\033[0m\n");;
        $charcoal->bootstrap($timestamp);
        $startupTime = $charcoal->diagnostics->startupTime / 1e6;
        fwrite(STDERR, "\033[33mStartup Time: \033[32m" . $startupTime . "ms\033[0m\n");



    }
}