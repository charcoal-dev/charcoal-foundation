<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Engine\Logs;

use App\Shared\Sapi\Cli\DomainScriptBase;
use Charcoal\App\Kernel\Support\DtoHelper;

/**
 * Represents a logging and context handling mechanism for managing flags,
 * arguments, dumps, log entries, and exceptions.
 */
final class LogContext
{
    private array $flags = [];
    private array $arguments = [];
    private array $dump = [];
    private array $logs = [];
    private array $exceptions = [];

    /**
     * @internal
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
    public function dump(string $name, string|int|bool|null|float $value): self
    {
        $this->dump[$name] = $value;
        return $this;
    }

    /**
     * @param string $log
     * @return $this
     */
    public function log(string $log): self
    {
        $this->logs[] = ["message" => $log, "timestamp" => microtime(true)];
        return $this;
    }

    /**
     * @api
     */
    public function setArgument(string $name, string|int|bool|null|float $value): self
    {
        $this->arguments[$name] = $value;
        return $this;
    }

    /**
     * @api
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
     * @api
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
     * @api
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
}