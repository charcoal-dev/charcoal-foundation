<?php
declare(strict_types=1);

/**
 * Represents the database table for email backlog processing within the mailer module.
 *
 * This class manages the structure and characteristics of a database table used
 * to queue and process emails.*/

namespace App\Shared\Foundation\Mailer\Backlog;

use App\Shared\Enums\DatabaseTables;
use App\Shared\Enums\Mailer\QueuedEmailStatus;
use App\Shared\Foundation\Mailer\MailerModule;
use Charcoal\App\Kernel\Orm\Db\OrmTableBase;
use Charcoal\Base\Enums\Charset;
use Charcoal\Database\Orm\Concerns\LobSize;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Represents the database table for email backlog processing within the mailer module.
 * @property MailerModule $module
 */
final class BacklogTable extends OrmTableBase
{
    public function __construct(MailerModule $module)
    {
        parent::__construct($module, DatabaseTables::MailerQueue, QueuedEmail::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->enumObject("status", QueuedEmailStatus::class)
            ->options(...QueuedEmailStatus::getCaseValues())
            ->default("pending");

        $cols->string("recipient")->length(80);
        $cols->string("sender")->length(80);
        $cols->string("subject")->length(255);
        $cols->blobBuffer("message")->size(LobSize::MEDIUM)->nullable();
        $cols->int("added_on")->bytes(4)->unSigned();
        $cols->int("attempts")->bytes(1)->unSigned()->default(0);
        $cols->int("last_attempt")->bytes(4)->unSigned()->nullable();
        $cols->string("error")->length(255)->nullable();
        $cols->setPrimaryKey("id");
    }

    protected function migrations(TableMigrations $migrations): void
    {
    }
}