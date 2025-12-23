<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Mailer\MailsQueue;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Mailer\Enums\MailQueueStatus;
use App\Shared\Mailer\MailerModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Database\Orm\Enums\LobSize;
use Charcoal\Database\Orm\Schema\Builder\ColumnsBuilder;
use Charcoal\Database\Orm\Schema\Builder\ConstraintsBuilder;
use Charcoal\Database\Orm\Schema\TableMigrations;

/**
 * Represents the database table structure and constraints for the mail queue system.
 */
final class MailsQueueTable extends OrmTableBase
{
    public function __construct(MailerModule $module)
    {
        parent::__construct($module, DatabaseTables::MailerQueue, MailQueueEntity::class);
    }

    /**
     * @param ColumnsBuilder $cols
     * @param ConstraintsBuilder $constraints
     */
    protected function structure(ColumnsBuilder $cols, ConstraintsBuilder $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->size(8)->unSigned()->autoIncrement();
        $cols->binaryFrame("checksum")->fixed(20);
        $cols->enumObject("status", MailQueueStatus::class)->options(...MailQueueStatus::getCaseValues());
        $cols->string("recipient")->length(64);
        $cols->string("subject")->length(255);
        $cols->blobBuffer("body")->size(LobSize::MEDIUM)->nullable();
        $cols->int("attempts")->size(1)->unSigned()->default(0);
        $cols->int("created_at")->size(4)->unSigned();
        $cols->int("last_attempt")->size(4)->unSigned()->nullable();
        $cols->string("error")->length(255)->nullable();
        $cols->setPrimaryKey("id");

        $constraints->addIndexComposite("idx_status_id")->columns("status", "id");
    }

    /**
     * @param TableMigrations $migrations
     */
    protected function migrations(TableMigrations $migrations): void
    {
    }
}