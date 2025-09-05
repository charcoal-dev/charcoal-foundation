<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Engine\Scripts;

use App\Shared\Core\Cli\DomainScriptBase;
use App\Shared\Core\Cli\LogPolicy;
use App\Shared\Enums\Databases;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Database\Orm\Migrations;

/**
 * Class Install
 * @package App\Sapi\Engine\Scripts
 * @api
 */
class Install extends DomainScriptBase
{
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
    }

    /**
     * @return void
     */
    protected function execScript(): void
    {
        $this->createDbTables();
        $this->createRequiredStoredObjects();
    }

    /**
     * @return void
     */
    private function createRequiredStoredObjects(): void
    {
        $app = $this->getAppBuild();
        $this->inline("Checking for required stored objects ... ");
        if (!isset($app->coreData->objectStore)) {
            $this->print("{red}ObjectStore not built");
            return;
        }

        $this->print("");
    }

    /**
     * @param string $objectClassname
     * @param \Closure $newInstance
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     * @api
     */
    protected function handleRequiredStoredObject(
        string   $objectClassname,
        \Closure $newInstance
    ): void
    {
        $objectStore = $this->getAppBuild()->coreData->objectStore;
        if (!class_exists($objectClassname)) {
            throw new \LogicException("Bad stored object classname");
        }

        $this->inline("\t{grey}Checking {yellow}" . ObjectHelper::baseClassName($objectClassname) . "{/}{grey} ... ");

        try {
            $objectStore->get($objectClassname, useCache: false);
            $this->print("{green}Exists");
            return;
        } catch (EntityNotFoundException) {
        }

        $newInstance = $newInstance();
        if (!$newInstance instanceof $objectClassname) {
            throw new \LogicException("Expected " . $objectClassname . " instance");
        }

        $objectStore->store($newInstance);
        $this->print("{green}Created");
    }

    /**
     * @return void
     */
    private function createDbTables(): void
    {
        $app = $this->getAppBuild();
        $dbDeclaredTables = $app->database->tables->getCollection();

        $installSequence = [];
        foreach ($dbDeclaredTables as $dbTag => $dbTables) {
            $dbTag = Databases::from($dbTag);
            $this->inline("Getting {invert}{yellow} " . $dbTag->name . " {/} database ... ");
            $dbInstance = $app->database->getDb($dbTag);
            $this->print("{grey}[{green}OK{grey}]{/}");

            $this->inline("{grey}Tables registered: {/}");
            $tablesCount = count($dbTables);
            $this->print("{yellow}" . $tablesCount . "{/}");

            $highestPriority = 0;
            /** @var OrmTableBase $tableInstance */
            foreach ($dbTables as $tableInstance) {
                if ($tableInstance->enum->getPriority() > $highestPriority) {
                    $highestPriority = $tableInstance->enum->getPriority();
                }

                $this->print(sprintf(
                    "{grey}[{green}+{grey}]{/} {cyan}%s{/}{grey} as {green}%s{/}{grey}",
                    get_class($tableInstance),
                    $tableInstance->name
                ), 200);

                $installSequence[$tableInstance->enum->getPriority()][] = [$dbInstance, $tableInstance];
            }

            $this->print("");
        }

        ksort($installSequence);
        foreach ($installSequence as $tables) {
            $this->print("");
            $this->print("");
            $this->print("");

            $progressIndex = 0;
            $tablesCount = count($tables);
            foreach ($tables as $table) {
                list($dbInstance, $tableInstance) = $table;

                $progressIndex++;
                $this->print("{goUp3}{atLineStart}{clearRight}{clearRight}");
                $this->print(sprintf("{grey}Progress: {/}%d{grey}/{yellow}%d", $progressIndex, $tablesCount));
                $this->print(sprintf("{grey}CREATE TABLE `{green}%s{/}{grey}` IF NOT EXISTS", $tableInstance->name), 200);
                $stmt = Migrations::createTable($tableInstance, true);
                $dbInstance->exec(implode("", $stmt));

                unset($dbInstance, $tableInstance, $stmt);
            }

            $this->print("{goUp3}{atLineStart}{clearRight}{clearRight}");
            $this->print(sprintf("{grey}Progress: {yellow}%d{grey}/{yellow}%d{/} ... {grey}[{green}Completed{grey}]", $tablesCount, $tablesCount));
            $this->print("");
        }
    }
}