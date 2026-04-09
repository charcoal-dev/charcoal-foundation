<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Clock\Clock;
use Charcoal\App\Kernel\Support\TypeCaster;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Cli\Events\State\RuntimeStatusChange;

/**
 * Provides domain-specific processing logic for handling application behavior.
 */
trait ConsoleExecutionTrait
{
    private(set) ?LogPolicy $logPolicy = null;
    private(set) ?ConsoleExecutionLogger $engineLog = null;

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

        // Capture Errors
        $this->cli->events->subscribe()->listen(RuntimeStatusChange::class,
            function (RuntimeStatusChange $event) {
                if ($event->exception) {
                    $this->getAppBuild()->diagnostics->error(
                        sprintf('Execution "%s" encountered %s',
                            $this->whoAmI ?? ObjectHelper::baseClassName(static::class),
                            $event->exception::class
                        ),
                        exception: $event->exception
                    );
                }
            });

        // Log Policy Declaration
        if (!isset($this->logPolicy)) {
            $this->logPolicy = $this->declareLogPolicy();
        }
    }

    /**
     * @return string|null
     */
    public function getCurrentUuid(): ?string
    {
        if ($this->engineLog) {
            return (string)$this->engineLog->logEntity->id;
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::getCurrentUuid();
    }

    /**
     * @param ConsoleExecutionLogger $logger
     * @return void
     */
    protected function setExecutionLogger(ConsoleExecutionLogger $logger): void
    {
        $this->engineLog = $logger;
    }

    /**
     * @return void
     */
    protected function clearExecutionLogger(): void
    {
        $this->engineLog = null;
    }

    /**
     * @return void
     */
    protected function hookBeforeExecutionStart(): void
    {
        // Late log policy declaration
        if (($this->logPolicy ?? null) === null) {
            $this->logPolicy = $this->declareLogPolicy();
        }

        if ($this->logPolicy?->status) {
            try {
                $this->setExecutionLogger($this->createEngineLog(
                    Clock::nowHighRes(),
                    $this->logPolicy->label,
                    $this->logPolicy->captureStateChanges
                ));
            } catch (\Exception $e) {
                $this->getAppBuild()->diagnostics->error("Failed to create EngineLog", exception: $e);
                return;
            }
        }
    }

    /**
     * @param bool $isSuccess
     * @return void
     */
    protected function hookAfterExecutionEnd(bool $isSuccess): void
    {
        if ($this->engineLog) {
            try {
                $this->getAppBuild()->telemetry->engineLogs->updateLog($this->engineLog->logEntity,
                    $this->state, Clock::nowHighRes());
            } catch (\Exception $e) {
                $this->getAppBuild()->diagnostics->error("Failed to finalise EngineLog", exception: $e);
                return;
            }
        }
    }

    /**
     * @return LogPolicy|null
     */
    protected function declareLogPolicy(): ?LogPolicy
    {
        return null;
    }

    /**
     * @throws \Charcoal\Base\Exceptions\WrappedException
     */
    public function createEngineLog(
        \DateTimeImmutable $timestamp,
        ?string            $label,
        bool               $captureStateChanges = false
    ): ConsoleExecutionLogger
    {
        return new ConsoleExecutionLogger($this, $label, $timestamp, $captureStateChanges);
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