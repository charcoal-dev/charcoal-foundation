<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Engine\Scripts;

use App\Shared\AppConstants;
use App\Shared\Cli\DomainScriptBase;
use App\Shared\Enums\Databases;
use Charcoal\App\Kernel\Enums\SecretsStoreType;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException;
use Charcoal\Base\Objects\ObjectHelper;
use Charcoal\Database\Orm\Migrations;
use Charcoal\Filesystem\Enums\Assert;

/**
 * Class Install
 * @package App\Sapi\Engine\Scripts
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
        $this->createDbTables();
        $this->checkSecretsLfsDirectory();
        $this->createRequiredStoredObjects();
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function checkSecretsLfsDirectory(): void
    {
        $charcoal = $this->getAppBuild();
        $this->print("Checking for Secrets KMS:");

        $index = 0;
        foreach ($charcoal->config->security->secretsStores as $storeId => $secretStoreConfig) {
            $index++;
            $this->inline(sprintf("%d. {yellow}%s{/} (KeySize: {magenta}%d Bytes{/}) ... {blue}%s{/} ... ",
                $index,
                $storeId,
                $secretStoreConfig->keySize->value,
                $secretStoreConfig->provider->getStoreType()->name
            ));

            if ($secretStoreConfig->provider->getStoreType() !== SecretsStoreType::LFS) {
                $this->print("{yellow}Skip");
                continue;
            }

            try {
                $secretsDefaultNamespacePath = $secretStoreConfig->ref->join(AppConstants::SECRETS_LOCAL_NAMESPACE);
                $secretsDefaultNamespace = $secretsDefaultNamespacePath->isDirectory();
                $asserts = DIRECTORY_SEPARATOR === "/" ? [Assert::Readable, Assert::Executable] : [Assert::Readable];
                $secretsDefaultNamespace->assert(...$asserts);
            } catch (\Throwable $e) {
                $this->print("{red}Failed");
                if (isset($secretsDefaultNamespacePath)) {
                    $this->print("\t{cyan}" . $secretsDefaultNamespacePath->path);
                }

                throw $e;
            }

            $this->print("{green}OK");
        }
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
            $objectStore->getObject($objectClassname, 1, useCache: false);
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