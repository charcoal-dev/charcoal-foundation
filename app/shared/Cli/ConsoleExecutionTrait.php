<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Support\TypeCaster;

/**
 * Provides domain-specific processing logic for handling application behavior.
 */
trait ConsoleExecutionTrait
{
    /**
     * @return void
     */
    private function initializeDomainLogic(): void
    {
        // Time-limit Enforcement
        if ($this instanceof DomainScriptBase) {
            if ($this->timeLimit <= 0
                && TypeCaster::toBool($this->cli->args->get("tty")) === false) {
                extension_loaded("pcntl") ? pcntl_alarm(30) :
                    throw new \RuntimeException("Cannot execute script with no time limit outside an interactive terminal");
            }
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
     */
    protected function printErrorsIfAny(int $tabs = 0, bool $compact = true): void
    {
        $this->cli->printErrors($tabs, $compact);
    }
}