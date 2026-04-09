<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\EngineLog;

use App\Shared\AppConstants;
use App\Shared\Cli\DomainProcessBase;
use App\Shared\Cli\DomainScriptBase;
use App\Shared\Enums\DatabaseTables;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityInsertableTrait;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityUpdatableTrait;
use Charcoal\Base\Exceptions\WrappedException;
use Charcoal\Cli\Enums\ExecutionState;
use Charcoal\Cli\Script\Arguments;
use Charcoal\Cli\Script\Flags;
use Charcoal\Vectors\Strings\StringVector;

/**
 * A repository class responsible for persisting and managing engine log entries
 * in the database. Provides methods for creating and updating log entries.
 */
final class EngineLogRepository extends OrmRepositoryBase
{
    use EntityInsertableTrait;
    use EntityUpdatableTrait;

    public function __construct()
    {
        parent::__construct(DatabaseTables::EngineLogs,
            AppConstants::ORM_CACHE_ERROR_HANDLING);
    }

    /**
     * @throws WrappedException
     */
    public function createLog(
        DomainScriptBase|DomainProcessBase $execScript,
        ?string                            $label,
        \DateTimeImmutable                 $timestamp,
    ): EngineLogEntity
    {
        if (!preg_match("/\A[\w\-_.]{2,40}\z/", $execScript->whoAmI)) {
            throw new \InvalidArgumentException("Invalid script name: " . $execScript->whoAmI);
        }

        $engineLog = new EngineLogEntity();
        $engineLog->type = $execScript instanceof DomainProcessBase ? "process" : "script";
        $engineLog->command = $execScript->whoAmI;
        $engineLog->label = $label;
        $engineLog->pid = getmypid();
        $engineLog->lastState = $execScript->state;
        $engineLog->flags = $this->encodeFlags($execScript->cli->flags);
        $engineLog->arguments = $this->encodeArguments($execScript->cli->args);
        $engineLog->startedOn = (float)sprintf("%.6f", (float)$timestamp->format("U.u"));
        $engineLog->updatedOn = null;

        try {
            $this->dbInsertAndSetId($engineLog, "id");
            return $engineLog;
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to create engine log entry");
        }
    }

    /**
     * @throws WrappedException
     */
    public function updateLog(
        EngineLogEntity    $logEntity,
        ExecutionState     $newState,
        \DateTimeImmutable $timestamp
    ): void
    {
        $logEntity->lastState = $newState;
        $logEntity->updatedOn = (float)sprintf("%.6f", (float)$timestamp->format("U.u"));

        try {
            $this->dbUpdateEntity($logEntity, new StringVector("lastState", "updatedOn"), $logEntity->id, "id");
        } catch (\Exception $e) {
            throw new WrappedException($e, "Failed to update engine log entry");
        }
    }

    /**
     * @throws WrappedException
     */
    private function encodeFlags(Flags $flags): ?string
    {
        try {
            $enabled = [];
            if ($flags->isQuick()) $enabled[] = "q";
            if ($flags->forceExec()) $enabled[] = "f";
            if ($flags->isVerbose()) $enabled[] = "v";
            if ($flags->isDebug()) $enabled[] = "d";
            if ($flags->useANSI()) $enabled[] = "a";
            if (!$enabled) {
                return null;
            }

            return json_encode($enabled, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WrappedException($e, "Failed to encode flags: " . $e->getMessage());
        }
    }

    /**
     * @throws WrappedException
     */
    private function encodeArguments(Arguments $args): ?string
    {
        $args = $args->getAll();
        if (!$args) {
            return null;
        }

        try {
            return json_encode($args, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new WrappedException($e, "Failed to encode arguments: " . $e->getMessage());
        }
    }
}