<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use App\Shared\CharcoalApp;
use App\Shared\Foundation\Engine\Logs\LogContext;
use App\Shared\Foundation\Engine\Logs\LogEntity;
use Charcoal\App\Kernel\ServerApi\Cli\AppCliHandler;
use Charcoal\Base\Objects\Traits\NoDumpTrait;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Cli\Output\FileWriter;

/**
 * Class ScriptExecutionLogger
 * @package App\Shared\Core\Cli
 * @deprecated
 */
class ScriptLogger
{
    private readonly CharcoalApp $app;
    private readonly AppCliHandler $cli;
    private ?FileWriter $outputBuffer = null;
    private ?string $outputBufferId = null;

    public readonly int $logId;

    use NotSerializableTrait;
    use NotCloneableTrait;
    use NoDumpTrait;

    /**
     * @param DomainScriptBase $script
     * @param ExecutionState $initialState
     * @param bool $scriptAwareContext
     * @param string|null $label
     * @param int $pid
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function __construct(
        DomainScriptBase $script,
        ExecutionState   $initialState,
        bool             $scriptAwareContext,
        ?string          $label,
        int              $pid,
    )
    {
        $this->app = $script->getAppBuild();
        $this->cli = $script->cli;

        if (!isset($this->app->engine->executionLog)) {
            throw new \LogicException("Engine does not have ExecutionLog component built");
        }

        $this->logEntity = $this->app->engine->executionLog->createLog(
            $script, $initialState, $label, $pid, $scriptAwareContext
        );

        $this->logId = $this->logEntity->id;
        $this->context = $this->logEntity->context();
    }

    /**
     * @param ExecutionState $newState
     * @return void
     */
    public function changeState(ExecutionState $newState): void
    {
        $this->logEntity->state = $newState;
    }

    /**
     * @return ExecutionState
     */
    public function getState(): ExecutionState
    {
        return $this->logEntity->state;
    }

    /**
     * @return $this
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function saveStateContext(): static
    {
        $this->app->engine->executionLog->updateContext($this->logEntity);
        return $this;
    }

    /**
     * @param bool $upsertState
     * @return $this
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function captureCpuStats(bool $upsertState): static
    {
        if ($upsertState) {
            $this->app->engine->logStats->upsert($this->logEntity);
        } else {
            $this->app->engine->logStats->insert($this->logEntity);
        }

        return $this;
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if ($this->outputBuffer) {
            $this->cli->removeOutputHandler($this->outputBufferId);
            $this->outputBuffer->endBuffer();
        }
    }
}