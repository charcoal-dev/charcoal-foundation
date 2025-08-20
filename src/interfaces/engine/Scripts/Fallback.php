<?php
declare(strict_types=1);

namespace App\Interfaces\Engine\Scripts;

use App\Shared\Core\Cli\DomainScriptBase;
use App\Shared\Core\Cli\LogPolicy;

/**
 * Class Fallback
 * @package App\Interfaces\Engine\Scripts
 * @api
 */
class Fallback extends DomainScriptBase
{
    private array $scripts = [];

    /**
     * @return LogPolicy
     */
    protected function declareExecutionLogging(): LogPolicy
    {
        return new LogPolicy(false);
    }

    /**
     * @return void
     */
    protected function onConstructHook(): void
    {
        $this->scripts[] = ["install", "Install the application", "yellow"];
    }

    /**
     * @return void
     */
    protected function execScript(): void
    {
        $this->print("Use the following scripts to perform specific tasks:");
        $this->print("");


        for ($i = 0; $i < count($this->scripts); $i++) {
            $this->print(sprintf("{green}%d.{/} {%s}{invert} %s {/}{grey} - %s",
                $i + 1,
                $this->scripts[$i][2] ?? "yellow",
                $this->scripts[$i][0],
                $this->scripts[$i][1]
            ));
        }

        $this->print("");
        $this->print("To run a script, use: {b}{cyan}./charcoal.sh [script-name]{/}");

    }
}
