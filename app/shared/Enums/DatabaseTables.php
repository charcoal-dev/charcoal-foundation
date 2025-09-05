<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums;

use Charcoal\App\Kernel\Contracts\Enums\DatabaseEnumInterface;
use Charcoal\App\Kernel\Contracts\Enums\TableRegistryEnumInterface;
use Charcoal\Database\Enums\DbDriver;

/**
 * Each constant defines a specific database table name as a string value. The constants
 * are categorized by their respective modules such as CoreData, Mailer, HTTP, and Engine.
 */
enum DatabaseTables: string implements TableRegistryEnumInterface
{
    /** Foundation Modules */
    case BruteForceControl = "bfc_index";
    case Countries = "countries";
    case DatabaseBackups = "db_backups";
    case ObjectStore = "object_store";
    case MailerQueue = "mails_queue";
    case HttpInterfaceLog = "http_if_log";
    case HttpCallLog = "http_call_log";
    case HttpProxies = "http_proxies";
    case EngineExecLog = "engine_exec_log";
    case EngineExecMetrics = "engine_exec_stats";
    case EngineQueue = "engine_queue";

    public function getTableName(): string
    {
        return $this->value;
    }

    public function getDatabase(): DatabaseEnumInterface
    {
        return Databases::Primary;
    }

    public function getPriority(): int
    {
        return match ($this) {
            self::HttpProxies => 100,
            default => 200
        };
    }

    public function getDriver(): DbDriver
    {
        return DbDriver::MYSQL;
    }
}