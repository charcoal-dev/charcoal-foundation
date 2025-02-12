<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use App\Shared\Core\Cache\CacheStore;
use App\Shared\Core\CipherKey;
use App\Shared\Core\Orm\AppOrmModule;
use App\Shared\Core\Orm\ModuleComponentEnum;
use App\Shared\Foundation\Mailer\Backlog\BacklogHandler;
use App\Shared\Foundation\Mailer\Backlog\BacklogTable;
use Charcoal\App\Kernel\Build\AppBuildPartial;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\App\Kernel\Orm\Db\DatabaseTableRegistry;
use Charcoal\Cipher\Cipher;

/**
 * Class MailerModule
 * @package App\Shared\Foundation\Mailer
 */
class MailerModule extends AppOrmModule
{
    public BacklogHandler $backlog;
    public readonly MailerService $service;

    /**
     * @param AppBuildPartial $app
     * @param Mailer[] $components
     */
    public function __construct(AppBuildPartial $app, array $components)
    {
        parent::__construct($app, CacheStore::PRIMARY, $components);
    }

    /**
     * @param AppBuildPartial $app
     * @return void
     */
    protected function declareChildren(AppBuildPartial $app): void
    {
        parent::declareChildren($app);
        $this->service = new MailerService($this);
    }

    /**
     * @param AbstractModuleComponent $resolveFor
     * @return Cipher
     */
    public function getCipher(AbstractModuleComponent $resolveFor): Cipher
    {
        return $this->app->cipher->get(CipherKey::PRIMARY);
    }

    /**
     * @param Mailer|ModuleComponentEnum $component
     * @param AppBuildPartial $app
     * @return bool
     */
    protected function includeComponent(Mailer|ModuleComponentEnum $component, AppBuildPartial $app): bool
    {
        if ($component === Mailer::BACKLOG) {
            $this->backlog = new BacklogHandler($this);
            return true;
        }

        return false;
    }

    /**
     * @param Mailer|ModuleComponentEnum $component
     * @param DatabaseTableRegistry $tables
     * @return bool
     */
    protected function createDbTables(Mailer|ModuleComponentEnum $component, DatabaseTableRegistry $tables): bool
    {
        if ($component === Mailer::BACKLOG) {
            $tables->register(new BacklogTable($this));
            return true;
        }

        return false;
    }
}