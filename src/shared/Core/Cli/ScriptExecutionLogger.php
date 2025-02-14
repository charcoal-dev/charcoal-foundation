<?php
declare(strict_types=1);

namespace App\Shared\Core\Cli;

use App\Shared\CharcoalApp;
use App\Shared\Foundation\Engine\ExecutionLog\ExecutionLogContext;
use App\Shared\Foundation\Engine\ExecutionLog\ExecutionLogEntity;
use Charcoal\App\Kernel\Interfaces\Cli\AppCliHandler;
use Charcoal\CLI\Console\FileWriter;
use Charcoal\OOP\Traits\NoDumpTrait;
use Charcoal\OOP\Traits\NotCloneableTrait;
use Charcoal\OOP\Traits\NotSerializableTrait;

/**
 * Class ScriptExecutionLogger
 * @package App\Shared\Core\Cli
 */
class ScriptExecutionLogger
{
    private readonly CharcoalApp $app;
    private readonly AppCliHandler $cli;
    private readonly ExecutionLogEntity $logEntity;
    private ?FileWriter $outputBuffer = null;
    private ?string $outputBufferId = null;
    private int $lifecycleBoundContext;

    public readonly int $logId;
    public readonly ExecutionLogContext $context;

    use NotSerializableTrait;
    use NotCloneableTrait;
    use NoDumpTrait;

    /**
     * @param AppAwareCliScript $script
     * @param CliScriptState $initialState
     * @param bool $scriptAwareContext
     * @param string|null $label
     * @param int $pid
     * @param bool $outputBuffering
     * @param bool $truncateOutputBufferFile
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(
        AppAwareCliScript $script,
        CliScriptState    $initialState,
        bool              $scriptAwareContext,
        ?string           $label,
        int               $pid,
        bool              $outputBuffering,
        bool              $truncateOutputBufferFile = true
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
        $this->lifecycleBoundContext = $this->app->lifecycle->bindContext($this->context);

        if ($outputBuffering) {
            $this->outputBufferId = $this->logEntity->script . ":" . $this->logEntity->id;
            $this->outputBuffer = new FileWriter(
                $this->app->directories->log->getFile("execution-log/" . $this->outputBufferId, true),
                !$truncateOutputBufferFile
            );

            $this->outputBuffer->startBuffer($script->cli);
            $this->cli->addOutputHandler($this->outputBuffer, $this->outputBufferId);
        }
    }

    /**
     * @param CliScriptState $newState
     * @return void
     */
    public function changeState(CliScriptState $newState): void
    {
        $this->logEntity->state = $newState;
    }

    /**
     * @return CliScriptState
     */
    public function getState(): CliScriptState
    {
        return $this->logEntity->state;
    }

    /**
     * @return $this
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function saveStateContext(): static
    {
        $this->app->engine->executionLog->updateContext($this->logEntity);
        return $this;
    }

    /**
     * @param bool $upsertState
     * @return $this
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
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
        $this->app->lifecycle->unbindContext($this->lifecycleBoundContext);
        if ($this->outputBuffer) {
            $this->cli->removeOutputHandler($this->outputBufferId);
            $this->outputBuffer->endBuffer();
        }
    }
}