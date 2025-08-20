<?php
declare(strict_types=1);

namespace Charcoal\Tests\Foundation;

use App\Shared\CharcoalApp;
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
    /** @noinspection PhpExceptionImmediatelyRethrownInspection */
    public function testCharcoalInitializerWithMonitor()
    {
        fwrite(STDERR, "\033[33mCharcoal App \033[0m\n");
        fwrite(STDERR, "\033[30mInitializing...\n");

        try {
            $charcoal = new CharcoalApp(
                AppEnv::Test,
                (new DirectoryPath(__DIR__ . "/build"))->node(),
                function (BuildStageEvents $events) {
                    fwrite(STDERR, "\033[36mBuild Stage:\033[0m \033[33m" . $events->name . "\033[0m\n");
                }
            );
        } catch (\Throwable $t) {
            /** @noinspection PhpUnhandledExceptionInspection */
            throw $t;
        }
    }
}