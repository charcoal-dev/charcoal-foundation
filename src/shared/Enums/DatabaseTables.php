<?php
declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\TableRegistryEnumInterface;

/**
 * Each constant defines a specific database table name as a string value. The constants
 * are categorized by their respective modules such as CoreData, Mailer, HTTP, and Engine.
 */
enum DatabaseTables: string implements TableRegistryEnumInterface
{
    # CoreData Module
    case BruteForceControl = "bfc_index";
    case Countries = "countries";
    case DatabaseBackups = "db_backups";
    case ObjectStore = "object_store";

    # Mailer Module
    case MailerQueue = "mails_queue";

    # HTTP Module
    case HttpInterfaceLog = "http_if_log";
    case HttpCallLog = "http_call_log";
    case HttpProxies = "http_proxies";

    # Engine Module
    case EngineExecLog = "engine_exec_log";
    case EngineExecMetric = "engine_exec_stats";
    case EngineQueue;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return $this->value;
    }

    /**
     * @return DatabaseEnum
     */
    public function getDatabase(): DatabaseEnum
    {
        return Databases::PRIMARY;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 1;
    }
}