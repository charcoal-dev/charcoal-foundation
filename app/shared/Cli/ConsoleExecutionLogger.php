<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use App\Shared\CharcoalApp;
use App\Shared\Telemetry\EngineLog\EngineLogEntity;
use Charcoal\App\Kernel\Clock\Clock;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Cli\Events\State\RuntimeStatusChange;
use Charcoal\Events\Subscriptions\Subscription;

/**
 * Handles the lifecycle and execution logging of a console-based script or process.
 */
final class ConsoleExecutionLogger
{
    use NotSerializableTrait;
    use NotCloneableTrait;
    use NoDumpTrait;

    private readonly CharcoalApp $app;
    public readonly EngineLogEntity $logEntity;
    private readonly ?Subscription $subscription;

    /**
     * @throws \Charcoal\Base\Exceptions\WrappedException
     */
    public function __construct(
        private readonly DomainScriptBase|DomainProcessBase $script,
        public readonly ?string                             $label,
        \DateTimeImmutable                                  $timestamp,
        public readonly bool                                $captureStateChanges = false
    )
    {
        $this->app = $script->getAppBuild();
        $this->logEntity = $this->app->telemetry->engineLogs->createLog($this->script, $this->label, $timestamp);
        if ($this->captureStateChanges) {
            $this->subscription = $this->script->cli->events->subscribe();
            $this->subscription->listen(RuntimeStatusChange::class,
                function () {
                    $this->captureState(Clock::now());
                });
        }
    }

    /**
     * Cleanup subscription on object destruction
     */
    public function __destruct()
    {
        $this->subscription?->unsubscribe();
    }

    /**
     * @throws \Charcoal\Base\Exceptions\WrappedException
     */
    public function update(\DateTimeImmutable $timestamp): void
    {
        $this->app->telemetry->engineLogs->updateLog($this->logEntity, $this->script->state, $timestamp);
    }

    /**
     * @throws \Charcoal\Base\Exceptions\WrappedException
     */
    public function captureState(\DateTimeImmutable $timestamp): void
    {
        $this->app->telemetry->engineMetrics->capture(
            $timestamp,
            $this->logEntity,
            $this->script->state,
            Diagnostics::getInstance()->metricsSnapshot()
        );
    }
}