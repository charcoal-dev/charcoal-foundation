<?php
declare(strict_types=1);

namespace App\Interfaces\Engine\Scripts;

use App\Shared\Core\Cli\AppAwareCliScript;
use App\Shared\Core\Cli\ScriptExecutionLogBinding;

/**
 * Class Fallback
 * @package App\Interfaces\Engine\Scripts
 */
class Fallback extends AppAwareCliScript
{
    /**
     * @return ScriptExecutionLogBinding
     */
    protected function declareExecutionLogging(): ScriptExecutionLogBinding
    {
        return new ScriptExecutionLogBinding(false);
    }

    /**
     * @return void
     */
    protected function onConstructHook(): void
    {
    }

    /**
     * @return void
     */
    protected function execScript(): void
    {
        $this->print("This is a fallback script.");
    }
}
