<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\AppLogs;

use App\Shared\AppConstants;
use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Diagnostics\LogEntry;
use Charcoal\App\Kernel\Orm\Repository\OrmRepositoryBase;
use Charcoal\App\Kernel\Orm\Repository\Traits\EntityInsertableTrait;
use Charcoal\App\Kernel\Support\DtoHelper;

/**
 * Repository responsible for managing the persistence of application logs.
 * Extends the base ORM repository to provide database operations specific to the AppLogs table.
 */
final class AppLogsRepository extends OrmRepositoryBase
{
    use EntityInsertableTrait;


    public function __construct()
    {
        parent::__construct(
            DatabaseTables::AppLogs,
            AppConstants::ORM_CACHE_ERROR_HANDLING
        );
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     */
    public function store(Interfaces $interface, ?string $uuid, LogEntry $logEntry): AppLogEntity
    {
        $logEntity = new AppLogEntity();
        $logEntity->interface = $interface;
        $logEntity->sapi = strtolower($interface->type()->name);
        $logEntity->uuid = $uuid;
        $logEntity->level = AppLogLevel::fromDiagnostics($logEntry->level);
        $logEntity->message = $logEntry->message;
        $logEntity->context = $logEntry->context ?
            json_encode(DtoHelper::createFrom($logEntry->context, 3, true, true, "**RECURSION**")) : null;
        $logEntity->exception = $logEntry->exception ?
            json_encode(DtoHelper::getExceptionObject($logEntry->exception)) : null;
        $logEntity->loggedAt = $logEntry->timestamp->getTimestamp();
        $this->dbInsertAndSetId($logEntity, "id");
        return $logEntity;
    }
}