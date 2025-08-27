<?php
declare(strict_types=1);

namespace App\Shared\Core\Db;

use App\Shared\CharcoalApp;
use App\Shared\Enums\Databases;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\Database\DatabaseClient;

/**
 * Class MultiDbTransaction
 * @package App\Shared\Core\Db
 * @deprecated
 */
class MultiDbTransaction
{
    public bool $logging = true;
    private array $databases = [];

    /**
     * @param CharcoalApp $app
     * @param Databases ...$database
     */
    public function __construct(private readonly CharcoalApp $app, Databases ...$database)
    {
        foreach ($database as $db) {
            $this->databases[$db->value] = $this->app->database->getDb($db);
        }
    }

    /**
     * @return void
     * @throws \Exception
     * @api
     */
    public function commitOrRollback(): void
    {
        try {
            $this->commit();
        } catch (\Exception $e) {
            $this->attemptRollback();
            throw $e;
        }
    }

    /**
     * @return void
     */
    private function attemptRollback(): void
    {
        try {
            $this->forEveryDb(function (DatabaseClient $db) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
            }, 'Rolling back transaction on "%s" DB', throwOnFirst: false);
        } catch (\Exception $rollbackError) {
            $this->addLogEntry("Rollback failed: " . $rollbackError->getMessage(), false);
        }
    }

    /**
     * @return void
     * @throws \Exception
     * @api
     */
    public function beginTransaction(): void
    {
        $this->forEveryDb(function (DatabaseClient $db) {
            $db->beginTransaction();
        }, 'Begin transaction on "%s" DB', throwOnFirst: true);
    }

    /**
     * @return void
     * @throws \Exception
     * @api
     */
    public function commit(): void
    {
        $this->forEveryDb(function (DatabaseClient $db) {
            $db->commit();
        }, 'Commiting transaction on "%s" DB', throwOnFirst: true);
    }

    /**
     * @return void
     * @throws \Exception
     * @api
     */
    public function rollBack(): void
    {
        $this->forEveryDb(function (DatabaseClient $db) {
            $db->rollBack();
        }, 'Rolling back transaction on "%s" DB', throwOnFirst: false);
    }

    /**
     * @param string $message
     * @param bool $success
     * @return void
     */
    private function addLogEntry(string $message, bool $success): void
    {
        if ($this->logging) {
            Diagnostics::app()->verbose($message, context: ["success" => $success]);
        }
    }

    /**
     * @param \Closure $closure
     * @param string $logMessage
     * @param bool $throwOnFirst
     * @return void
     * @throws \Exception
     */
    private function forEveryDb(
        \Closure $closure,
        string   $logMessage,
        bool     $throwOnFirst = false): void
    {
        $caught = null;
        foreach ($this->databases as $key => $db) {
            try {
                $closure($db);
                $this->addLogEntry(sprintf($logMessage, $key), true);
            } catch (\Exception $e) {
                $this->addLogEntry(sprintf($logMessage, $key), false);
                if ($throwOnFirst) {
                    throw $e;
                }

                if (!$caught) {
                    $caught = $e;
                }

                continue;
            }
        }

        if ($caught) {
            throw $caught;
        }
    }
}