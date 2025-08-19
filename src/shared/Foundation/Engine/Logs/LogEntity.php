<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine\Logs;

use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Buffers\Buffer;
use Charcoal\Cli\Enums\ExecutionState;

/**
 * This class represents a log entity used to capture and store information
 * about script executions and their related context. It extends the
 * functionality provided by the base ORM entity.
 */
final class LogEntity extends OrmEntityBase
{
    public int $id;
    public string $script;
    public ?string $label;
    public ExecutionState $state;
    public Buffer $context;
    public int $pid;
    public float $startedOn;
    public ?float $updatedOn;

    private ?LogContext $contextObject = null;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        throw new \LogicException(static::class . " does not need to be serialized");
    }

    /**
     * @param LogContext $context
     * @return void
     */
    public function setContextObject(LogContext $context): void
    {
        if (isset($this->context) || isset($this->contextObject)) {
            throw new \LogicException("ExecutionLogContext instance cannot be overridden");
        }

        $this->contextObject = $context;
        $this->context = new Buffer(serialize($context));
    }

    /**
     * @return LogContext
     */
    public function context(): LogContext
    {
        if ($this->contextObject) {
            return $this->contextObject;
        }

        $contextObject = unserialize($this->context->raw(), ["allowed_classes" => [LogContext::class]]);
        if (!$contextObject instanceof LogContext) {
            throw new \RuntimeException(
                sprintf('%s encountered value of type "%s"',
                    __METHOD__,
                    is_object($contextObject) ? get_class($contextObject) : gettype($contextObject)
                )
            );
        }

        return $this->contextObject;
    }
}