<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use App\Shared\CharcoalApp;
use App\Shared\Concerns\NormalizedStorageKeysTrait;
use App\Shared\Concerns\PendingModuleComponents;
use App\Shared\Enums\CacheStores;
use App\Shared\Foundation\Mailer\Backlog\BacklogHandler;
use App\Shared\Foundation\Mailer\Backlog\BacklogTable;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cache\CacheClient;

/**
 * Represents the Mailer module within the application, providing functionality
 * for managing email-related services, backlogs, and database table declarations.
 * @property-read CharcoalApp $app
 */
final class MailerModule extends OrmModuleBase
{
    use NormalizedStorageKeysTrait;
    use PendingModuleComponents;

    public readonly BacklogHandler $backlog;
    public readonly MailerService $service;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->backlog = new BacklogHandler($this);
        $this->service = new MailerService();
    }

    /**
     * @param TableRegistry $tables
     * @return void
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new BacklogTable($this));
    }

    /**
     * @return array|array[]|\string[][]
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["backlog"] = $this->backlog;
        $data["service"] = $this->service;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->backlog = $data["backlog"];
        $this->service = $data["service"];
    }

    /**
     * @return CacheClient|null
     */
    public function getCacheStore(): ?CacheClient
    {
        return $this->app->cache->getStore(CacheStores::Primary);
    }
}