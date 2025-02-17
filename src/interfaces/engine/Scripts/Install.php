<?php
declare(strict_types=1);

namespace App\Interfaces\Engine\Scripts;

use App\Shared\Core\Cli\AppAwareCliScript;
use App\Shared\Core\Cli\ScriptExecutionLogBinding;
use Charcoal\App\Kernel\Orm\AbstractOrmModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Migrations;

/**
 * Class Install
 * @package App\Interfaces\Engine\Scripts
 */
class Install extends AppAwareCliScript
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

    protected function execScript(): void
    {
        $this->createDbTables();
    }

    /**
     * @return void
     */
    private function createDbTables(): void
    {
        $app = $this->getAppBuild();
        $dbRegistry = $app->databases->orm;
        $dbDeclaredTables = $dbRegistry->getCollection();

        foreach ($dbDeclaredTables as $dbTag => $dbTables) {
            $this->inline("Getting {invert}{yellow} " . $dbTag . " {/} database ... ");
            $dbInstance = $app->databases->getDb($dbTag);
            $this->print("{grey}[{green}OK{grey}]{/}");
            $this->inline("{grey}Tables registered: {/}");
            $tablesCount = count($dbTables);
            $this->print("{yellow}" . $tablesCount);

            /**
             * @var string $tableTab
             * @var AbstractOrmTable $tableInstance
             */
            foreach ($dbTables as $tableInstance) {
                $this->print(sprintf(
                    "{grey}[{green}+{grey}]{/} {cyan}%s{/}{grey} as {green}%s{/}{grey}",
                    get_class($tableInstance),
                    $tableInstance->name
                ), 200);
            }

            $this->print("");
            $this->print("");
            $this->print("");

            $progressIndex = 0;
            foreach ($dbTables as $tableInstance) {
                $progressIndex++;
                $this->print("{goUp3}{atLineStart}{clearRight}{clearRight}");
                $this->print(sprintf("{grey}Progress: {/}%d{grey}/{yellow}%d", $progressIndex, $tablesCount));
                $this->print(sprintf("{grey}CREATE TABLE `{green}%s{/}{grey}` IF NOT EXISTS", $tableInstance->name));
                $stmt = Migrations::createTable($dbInstance, $tableInstance, true);
                //$dbInstance->exec(implode("", $stmt));
            }

            $this->print("{goUp3}{atLineStart}{clearRight}{clearRight}");
            $this->print(sprintf("{grey}Progress: {yellow}%d{grey}/{yellow}%d{/} ... {grey}[{green}Completed{grey}]", $tablesCount, $tablesCount));
            $this->print("");
        }
    }
}