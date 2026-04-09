<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\EngineLog;

use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Cli\Enums\ExecutionState;

/**
 * Represents an entity for logging engine-related data.
 */
final class EngineLogEntity extends OrmEntityBase
{
    public int $id;
    public string $type;
    public string $command;
    public ?string $label;
    public int $pid;
    public ExecutionState $lastState;
    public ?string $flags;
    public ?string $arguments;
    public float $startedOn;
    public ?float $updatedOn;

    private ?array $_flags = null;
    private ?array $_arguments = null;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }

    /**
     * @return array|null
     */
    public function getFlags(): ?array
    {
        if (!$this->flags) {
            return null;
        }

        if ($this->_flags === null) {
            try {
                $this->_flags = json_decode($this->flags, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                Diagnostics::app()->warning("Failed to decode EngineLog flags: " . $this->id, exception: $e);
                $this->_flags = [];
            }
        }

        return $this->_flags;
    }

    /**
     * @return array|null
     */
    public function getArguments(): ?array
    {
        if (!$this->arguments) {
            return null;
        }

        if ($this->_arguments === null) {
            try {
                $this->_arguments = json_decode($this->arguments, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                Diagnostics::app()->warning("Failed to decode EngineLog arguments: " . $this->id, exception: $e);
                $this->_arguments = [];
            }
        }

        return $this->_arguments;
    }
}