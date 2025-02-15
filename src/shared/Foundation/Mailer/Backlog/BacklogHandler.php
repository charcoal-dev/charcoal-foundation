<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Backlog;

use App\Shared\AppDbTables;
use App\Shared\Foundation\Mailer\MailerModule;
use Charcoal\App\Kernel\Orm\AbstractOrmRepository;
use Charcoal\App\Kernel\Orm\Repository\EntityInsertableTrait;
use Charcoal\Buffers\Buffer;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Class BacklogHandler
 * @package App\Shared\Foundation\Mailer\Backlog
 * @property MailerModule $module
 */
class BacklogHandler extends AbstractOrmRepository
{
    use EntityInsertableTrait;

    public function __construct(MailerModule $module)
    {
        parent::__construct($module, AppDbTables::MAILER_BACKLOG);
    }

    /**
     * @param string $sender
     * @param string $recipient
     * @param string $subject
     * @param CompiledMimeMessage|null $message
     * @param QueuedEmailStatus $status
     * @param string|null $errorMsg
     * @return QueuedEmail
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function createQueuedEmail(
        string               $sender,
        string               $recipient,
        string               $subject,
        ?CompiledMimeMessage $message,
        QueuedEmailStatus    $status,
        ?string              $errorMsg = null
    ): QueuedEmail
    {
        $queued = new QueuedEmail();
        $queued->status = $status;
        $queued->recipient = $recipient;
        $queued->subject = $subject;
        $queued->sender = $sender;
        $queued->message = $message ? new Buffer(serialize($message)) : null;
        $queued->addedOn = time();
        $queued->error = null;
        if ($status === QueuedEmailStatus::SENT ||
            $status === QueuedEmailStatus::RETRYING ||
            $status === QueuedEmailStatus::FAILED) {
            $queued->attempts = 1;
            $queued->lastAttempt = $queued->addedOn;
            if ($status !== QueuedEmailStatus::SENT && $errorMsg) {
                $queued->error = substr($errorMsg, 0, 255);
            }
        } else {
            $queued->attempts = 0;
            $queued->lastAttempt = null;
        }

        $this->dbInsertAndSetId($queued, "id");
        return $queued;
    }
}