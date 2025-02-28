<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine\ExecutionLog;

use App\Shared\AppDbTables;
use App\Shared\Core\Cli\AppAwareCliScript;
use App\Shared\Core\Cli\CliScriptState;
use App\Shared\Foundation\Engine\EngineModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Repository\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\EntityUpdatableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\OOP\OOP;
use Charcoal\OOP\Vectors\StringVector;

/**
 * Class ExecutionLogOrm
 * @package App\Shared\Foundation\Engine\ExecutionLog
 * @property EngineModule $module
 */
class ExecutionLogOrm extends AbstractOrmRepository
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    /**
     * @param EngineModule $module
     */
    public function __construct(EngineModule $module)
    {
        parent::__construct($module, AppDbTables::ENGINE_EXEC_LOG);
    }

    /**
     * @param AppAwareCliScript $script
     * @param CliScriptState $initialState
     * @param string|null $label
     * @param int $pid
     * @param bool $scriptAwareContext
     * @return ExecutionLogEntity
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function createLog(
        AppAwareCliScript $script,
        CliScriptState    $initialState,
        ?string           $label,
        int               $pid,
        bool              $scriptAwareContext
    ): ExecutionLogEntity
    {
        $execLog = new ExecutionLogEntity();
        $execLog->setContextObject(new ExecutionLogContext($scriptAwareContext ? $script : null));
        $execLog->script = OOP::baseClassName($script->scriptClassname);
        $execLog->label = $label;
        $execLog->state = $initialState;
        $execLog->pid = $pid;
        $execLog->startedOn = round(microtime(true), 4);
        $execLog->updatedOn = null;
        $this->dbInsertAndSetId($execLog, "id");
        return $execLog;
    }

    /**
     * @param ExecutionLogEntity $execLog
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function updateContext(ExecutionLogEntity $execLog): void
    {
        $execLog->context = new Buffer(serialize($execLog->context()));
        $execLog->updatedOn = round(microtime(true), 4);

        $this->dbUpdateEntity($execLog, new StringVector("state", "context", "updatedOn"), $execLog->id, "id");
    }
}