<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Engine\Logs;

use App\Shared\Core\Cli\DomainScriptBase;
use Charcoal\App\Kernel\Support\DtoHelper;

/**
 * Class ExecutionLogContext
 * @package App\Shared\Foundation\Engine\ExecutionLog
 */
class ExecutionLogContext
{
    private array $flags = [];
    private array $arguments = [];
    private array $dump = [];
    private array $logs = [];
    private array $exceptions = [];

    /**
     * @param DomainScriptBase|null $script
     */
    public function __construct(?DomainScriptBase $script)
    {
        if ($script) {
            $this->flags = [
                "quick" => $script->cli->flags->isQuick(),
                "force" => $script->cli->flags->forceExec(),
                "debug" => $script->cli->flags->isDebug(),
                "verbose" => $script->cli->flags->isVerbose(),
                "ansi" => $script->cli->flags->useANSI()
            ];

            $this->arguments = $script->cli->args->getAll();
        }
    }

    /**
     * @param \Throwable $t
     * @return void
     */
    public function logException(\Throwable $t): void
    {
        $this->exceptions[] = DtoHelper::getExceptionObject($t);
    }

    /**
     * @param string $name
     * @param string|int|bool|float|null $value
     * @return $this
     */
    public function dump(string $name, string|int|bool|null|float $value): static
    {
        $this->dump[$name] = $value;
        return $this;
    }

    /**
     * @param string $log
     * @return $this
     */
    public function log(string $log): static
    {
        $this->logs[] = ["message" => $log, "timestamp" => microtime(true)];
        return $this;
    }

    /**
     * @param string $name
     * @param string|int|bool|float|null $value
     * @return $this
     */
    public function setArgument(string $name, string|int|bool|null|float $value): static
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * @param string $arg
     * @return bool
     */
    public function unsetArg(string $arg): bool
    {
        if (isset($this->arguments[$arg])) {
            unset($this->arguments[$arg]);
            return true;
        }

        return false;
    }

    /**
     * @param string $flag
     * @return bool
     */
    public function unsetFlag(string $flag): bool
    {
        if (isset($this->flags[$flag])) {
            unset($this->flags[$flag]);
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function createDto(): array
    {
        return [
            "flags" => $this->flags,
            "arguments" => $this->arguments,
            "dump" => $this->dump,
            "logs" => $this->logs,
            "exceptions" => $this->exceptions
        ];
    }

    /**
     * @param string $level
     * @param string $entry
     * @param bool|int|string|null $value
     * @return void
     */
    public function entryFromLifecycle(string $level, string $entry, bool|int|string|null $value = null): void
    {
        $this->dump(sprintf("%s[%s]", $level, $entry), $value);
    }

    /**
     * @param \Throwable $t
     * @return void
     */
    public function exceptionFromLifecycle(\Throwable $t): void
    {
        $this->logException($t);
    }
}