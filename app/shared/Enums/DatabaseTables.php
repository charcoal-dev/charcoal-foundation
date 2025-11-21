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
    /** @for Foundation Modules */
    case ObjectStore = "object_store";
    case DatabaseBackups = "db_backups";
    case BruteForceControl = "bfc_index";
    case Countries = "countries";
    case MailerQueue = "mails_queue";
    case HttpProxies = "http_proxies";

    /** @for Telemetry Module */
    case AppLogs = "app_logs";
    case AppMetrics = "app_metrics";
    case HttpIngress = "http_ingress";
    case HttpEgress = "http_egress";

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