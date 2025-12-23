<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Engine\Scripts;

use App\Shared\Cli\DomainScriptBase;
use App\Shared\Contracts\PersistedConfigInterface;
use App\Shared\CoreData\ObjectStore\StoredObjectEntity;
use App\Shared\CoreData\Support\StoredObjectPointer;
use App\Shared\Enums\Databases;
use App\Shared\Enums\SecretKeys;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException;
use Charcoal\App\Kernel\Support\ErrorHelper;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Database\Orm\Migrations;

/**
 * The Installation class is responsible for the execution of a domain-related installation script.
 * It performs tasks such as checking secret keys, creating required database tables,
 * and ensuring the existence of required stored objects.
 */
class Install extends DomainScriptBase
{
    /**
     * @return void
     */
    protected function onConstructHook(): void
    {
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function execScript(): void
    {
        $this->checkSecretKeys();
        $this->createDbTables();
        $this->createRequiredStoredObjects();
    }

    /**
     * @return void
     * @throws \Exception
     */
    protected function checkSecretKeys(): void
    {
        $this->inline("Checking for configured secret keys: ");
        $secretKeys = SecretKeys::cases();
        $secretKeysCount = count($secretKeys);
        $this->print(sprintf("{grey}[{%s}%d{/}{grey}]", $secretKeysCount > 0 ? "green" : "yellow", $secretKeysCount));

        $charcoal = $this->getAppBuild();
        $secretsManager = $charcoal->security->secrets;
        $index = 0;
        foreach ($secretKeys as $secretKeyEnum) {
            unset($secretKeyLength);

            $index++;
            $this->inline(sprintf("   {grey}[{yellow}%d{/}{grey}]{/} {cyan}%s ... ", $index, $secretKeyEnum->name));
            try {
                $secretKeyBuffer = $secretsManager->resolveSecretEnum($secretKeyEnum);
                $secretKeyBuffer->useSecretEntropy(function (string $entropy) use (&$secretKeyLength) {
                    $secretKeyLength = strlen($entropy);
                });

                $this->print(sprintf("{green}%d Bytes", $secretKeyLength));
            } catch (\Exception $e) {
                $this->print("{red}Error");
                $this->print("   {red}" . ErrorHelper::exception2String($e));
                throw $e;
            }
        }

        $this->print("");
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
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Database\Orm\Exceptions\OrmQueryException
     * @api
     */
    protected function handleRequiredStoredObject(
        StoredObjectPointer $objectPointer,
        \Closure            $newInstance
    ): void
    {
        if (!class_exists($objectPointer->fqcn)) {
            throw new \LogicException("Bad stored object classname");
        }

        $this->inline("\t{grey}Checking {yellow}"
            . ObjectHelper::baseClassName($objectPointer->fqcn) . "{/}{grey} ... ");

        $objectStore = $this->getAppBuild()->coreData->objectStore;

        try {
            $objectStore->getObject($objectPointer, useCache: false);
            $this->print("{green}Exists");
            return;
        } catch (EntityNotFoundException) {
        }

        $newInstance = $newInstance();
        /** @var PersistedConfigInterface $newInstance */
        if (!$newInstance instanceof $objectPointer->fqcn) {
            throw new \LogicException("Expected " . $objectPointer->fqcn . " instance");
        }

        $objectStore->store(StoredObjectEntity::plainEnvelope(
            $objectPointer->ref,
            $newInstance,
            $objectPointer->version
        ));

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
            $dbTag = Databases::find($dbTag, caseSensitive: false);
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