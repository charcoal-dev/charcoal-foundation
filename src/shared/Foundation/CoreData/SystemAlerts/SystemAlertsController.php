<?php
declare(strict_types=1);

namespace App\Shared\Foundation\CoreData\SystemAlerts;

use App\Shared\AppDbTables;
use App\Shared\Foundation\CoreData\CoreDataModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Repository\EntityInsertableTrait;
use Charcoal\Buffers\Buffer;

/**
 * Class SystemAlertsController
 * @package App\Shared\Foundation\CoreData\SystemAlerts
 * @property CoreDataModule $module
 */
class SystemAlertsController extends AbstractOrmRepository
{
    use EntityInsertableTrait;

    /**
     * @param CoreDataModule $module
     */
    public function __construct(CoreDataModule $module)
    {
        parent::__construct($module, AppDbTables::SYSTEM_ALERTS);
    }

    /**
     * @param SystemAlertLevel $level
     * @param string $message
     * @param SystemAlertContext|null $context
     * @param AlertTraceProviderInterface|null $trace
     * @param bool $alertIdRequired
     * @return SystemAlertEntity
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Throwable
     */
    public function raise(
        SystemAlertLevel             $level,
        string                       $message,
        ?SystemAlertContext          $context,
        ?AlertTraceProviderInterface $trace,
        bool                         $alertIdRequired = false,
    ): SystemAlertEntity
    {
        $alert = new SystemAlertEntity();
        $alert->level = $level;
        $alert->message = $message;
        $alert->timestamp = time();
        $alert->traceInterface = $trace?->getTraceInterface();
        $alert->traceQid = $trace?->getTraceId();
        $alert->context = $context ? new Buffer(serialize($context)) : null;

        $alertIdRequired ? $this->dbInsertAndSetId($alert, "id") : $this->dbInsert($alert);
        $this->module->app->onSystemAlert($alert);
        return $alert;
    }
}