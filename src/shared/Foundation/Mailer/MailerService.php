<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;

/**
 * Class MailerService
 * @package App\Shared\Foundation\Mailer
 * @property MailerModule $module
 */
class MailerService extends AbstractModuleComponent
{
    public readonly bool $hasBacklog;

    /**
     * @param MailerModule $module
     */
    public function __construct(MailerModule $module)
    {
        parent::__construct($module);
        $this->hasBacklog = isset($module->backlog);
    }

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::collectSerializableData();
        $data["hasBacklog"] = $this->hasBacklog;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function onUnserialize(array $data): void
    {
        parent::onUnserialize($data);
        /** @noinspection PhpSecondWriteToReadonlyPropertyInspection */
        $this->hasBacklog = $data["hasBacklog"];
    }

    /**
     * @param CompiledMimeMessage|Message $message
     * @return CompiledMimeMessage
     * @throws \Charcoal\Mailer\Exception\EmailComposeException
     */
    private function getCompiledMimeMessage(CompiledMimeMessage|Message $message): CompiledMimeMessage
    {
        if (!$message instanceof CompiledMimeMessage) {
            $message = $message->compile();
        }

        if (strlen($message->compiledMimeBody) > (5 * 1048576)) {
            throw new \RuntimeException("Compiled MIME length exceeds hard-limit of 5MB");
        }

        return $message;
    }
}