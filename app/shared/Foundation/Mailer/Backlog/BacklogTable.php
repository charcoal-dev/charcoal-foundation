<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Backlog;

use App\Shared\Context\AppDbTables;
use App\Shared\Foundation\Mailer\MailerModule;
use Charcoal\App\Kernel\Orm\Db\AbstractOrmTable;
use Charcoal\Database\ORM\Schema\Charset;
use Charcoal\Database\ORM\Schema\Columns;
use Charcoal\Database\ORM\Schema\Constraints;
use Charcoal\Database\ORM\Schema\TableMigrations;

/**
 * Class BacklogTable
 * @package App\Shared\Foundation\Mailer\Backlog
 * @property MailerModule $module
 */
class BacklogTable extends AbstractOrmTable
{
    public function __construct(MailerModule $module)
    {
        parent::__construct($module, AppDbTables::MAILER_BACKLOG, QueuedEmail::class);
    }

    protected function structure(Columns $cols, Constraints $constraints): void
    {
        $cols->setDefaultCharset(Charset::ASCII);

        $cols->int("id")->bytes(8)->unSigned()->autoIncrement();
        $cols->enumObject("status", QueuedEmailStatus::class)->options(...QueuedEmailStatus::getOptions())->default("pending");
        $cols->string("recipient")->length(80);
        $cols->string("sender")->length(80);
        $cols->string("subject")->length(255);
        $cols->blobBuffer("message")->size("medium")->nullable();
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