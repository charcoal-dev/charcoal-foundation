<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Engine\Scripts;

use App\Shared\Cli\DomainScriptBase;

/**
 * Fallback/Default script for CLI console
 */
final class Fallback extends DomainScriptBase
{
    /** @var array<array{0: string, 1: string, 2?: string}> */
    private array $scripts = [
        ["install", "Install the application", "yellow"]
    ];

    /**
     * @return void
     */
    protected function exec(): void
    {
        $this->print("Use the following scripts to perform specific tasks:");
        $this->print("");

        foreach ($this->scripts as $index => $script) {
            $this->print(sprintf("{green}%d.{/} {%s}{invert} %s {/}{grey} - %s",
                $index + 1,
                $script[2] ?? "yellow",
                $script[0],
                $script[1]
            ));
        }

        $this->print("");
        $this->print("To run a script, use: {b}{cyan}./charcoal.sh [script-name]{/}");
    }
}
