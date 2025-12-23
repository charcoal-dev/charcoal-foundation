<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Mailer;

use App\Shared\CharcoalApp;
use App\Shared\Enums\SecretKeys;
use App\Shared\Mailer\MailsQueue\MailsQueueRepository;
use App\Shared\Mailer\MailsQueue\MailsQueueTable;
use App\Shared\Traits\OrmModuleTrait;
use Charcoal\App\Kernel\Domain\ModuleSecurityBindings;
use Charcoal\App\Kernel\Orm\Db\TableRegistry;
use Charcoal\App\Kernel\Orm\Module\OrmModuleBase;
use Charcoal\Cipher\Cipher;

/**
 * Represents the MailerModule, which provides functionality for handling
 * email operations within the application.
 * @property CharcoalApp $app
 */
final class MailerModule extends OrmModuleBase
{
    use OrmModuleTrait;

    public readonly MailsQueueRepository $queue;
    private ?EmailService $emailService = null;

    public function __construct(CharcoalApp $app)
    {
        parent::__construct($app);
        $this->queue = new MailsQueueRepository();
    }

    /**
     * @param TableRegistry $tables
     */
    protected function declareDatabaseTables(TableRegistry $tables): void
    {
        $tables->register(new MailsQueueTable($this));
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->queue = $data["queue"];
        parent::__unserialize($data);
    }

    /**
     * @return ModuleSecurityBindings
     */
    protected function declareSecurityBindings(): ModuleSecurityBindings
    {
        return new ModuleSecurityBindings(
            Cipher::AES_256_GCM,
            SecretKeys::Mailer
        );
    }

    /**
     * @return bool
     */
    public function isEmailServiceLoaded(): bool
    {
        return $this->emailService !== null;
    }

    /**
     * @return EmailService
     */
    public function emails(): EmailService
    {
        if ($this->emailService) {
            return $this->emailService;
        }

        return $this->emailService = new EmailService($this->app);
    }
}