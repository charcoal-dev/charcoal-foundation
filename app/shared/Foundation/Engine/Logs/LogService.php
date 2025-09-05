<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Engine\Logs;

use App\Shared\Core\Cli\DomainScriptBase;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Foundation\Engine\EngineModule;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityUpdatableTrait;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Buffers\Buffer;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Vectors\Strings\StringVector;

/**
 * Service class responsible for handling execution logs in the database.
 * Extends functionality from OrmRepositoryBase and uses traits for entity insertion and update operations.
 * @property EngineModule $module
 */
final class LogService extends OrmRepositoryBase
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    public function __construct(EngineModule $module)
    {
        parent::__construct($module, DatabaseTables::EngineExecLog);
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function createLog(
        DomainScriptBase $script,
        ExecutionState   $initialState,
        ?string          $label,
        int              $pid,
        bool             $scriptAwareContext
    ): LogEntity
    {
        $execLog = new LogEntity();
        $execLog->setContextObject(new LogContext($scriptAwareContext ? $script : null));
        $execLog->script = ObjectHelper::baseClassName($script->scriptClassname);
        $execLog->label = $label;
        $execLog->state = $initialState;
        $execLog->pid = $pid;
        $execLog->startedOn = round(microtime(true), 4);
        $execLog->updatedOn = null;
        $this->dbInsertAndSetId($execLog, "id");
        return $execLog;
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function updateContext(LogEntity $execLog): void
    {
        $execLog->context = new Buffer(serialize($execLog->context()));
        $execLog->updatedOn = round(microtime(true), 4);

        $this->dbUpdateEntity($execLog, new StringVector("state", "context", "updatedOn"), $execLog->id, "id");
    }
}