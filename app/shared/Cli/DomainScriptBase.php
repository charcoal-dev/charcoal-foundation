<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\ServerApi\Cli\AppCliHandler;
use Charcoal\App\Kernel\ServerApi\Cli\AppCliScript;
use Charcoal\App\Kernel\Support\TypeCaster;

/**
 * Base class for domain-specific scripts, extending the functionality
 * of the AppCliScript class by enforcing time limits and providing utility
 * methods for interacting with the application and error reporting.
 */
abstract class DomainScriptBase extends AppCliScript
{
    /**
     * @param AppCliHandler $cli
     */
    public function __construct(AppCliHandler $cli)
    {
        parent::__construct($cli);

        // Time-limit Enforcement
        if ($this->timeLimit <= 0
            && TypeCaster::toBool($this->cli->args->get("tty")) === false) {
            extension_loaded("pcntl") ? pcntl_alarm(30) :
                throw new \RuntimeException('Cannot execute script with no time limit outside an interactive terminal');
        }
    }

    /**
     * @return CharcoalApp
     */
    public function getAppBuild(): CharcoalApp
    {
        /** @var CharcoalApp */
        return $this->cli->app;
    }

    /**
     * @param int $tabs
     * @param bool $compact
     * @return void
     * @api
     */
    protected function printErrorsIfAny(int $tabs = 0, bool $compact = true): void
    {
        $this->cli->printErrors($tabs, $compact);
    }
}