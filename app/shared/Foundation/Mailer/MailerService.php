<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\Persisted\MailerConfig;
use App\Shared\Core\Config\Traits\PersistedConfigResolverTrait;
use App\Shared\Enums\Mailer\EmailTemplate;
use App\Shared\Enums\Mailer\MailDispatchMode;
use App\Shared\Enums\Mailer\QueuedEmailStatus;
use App\Shared\Exceptions\Mailer\EmailDeliveryException;
use App\Shared\Exceptions\Mailer\EmailServiceException;
use Charcoal\App\Kernel\Contracts\Domain\ModuleBindableInterface;
use Charcoal\App\Kernel\Support\ErrorHelper;
use Charcoal\Mailer\Mailer as CharcoalMailer;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;
use Charcoal\Mailer\Message\Sender;
use Charcoal\Mailer\Templating\RawTemplatedEmail;
use Charcoal\Mailer\TemplatingEngine;

/**
 * Service class for handling mail operations including email creation, dispatching, and queuing.
 * Implements functionality to create templated emails, send emails via a mailer
 * agent, and manage email dispatching and queuing.
 * @property MailerModule $module
 */
final class MailerService implements ModuleBindableInterface
{
    use PersistedConfigResolverTrait;

    private ?MailerConfig $config = null;
    private ?CharcoalMailer $client = null;
    private ?TemplatingEngine $templating = null;

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data["config"] = null;
        $data["client"] = null;
        $data["templating"] = null;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->config = null;
        $this->client = null;
        $this->templating = null;
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     * @throws \Charcoal\Mailer\Exceptions\MailerException
     * @api
     */
    public function createTemplatedEmail(
        string        $messageFilepath,
        string        $subject,
        ?string       $preHeader = null,
        EmailTemplate $template = EmailTemplate::DEFAULT,
        bool          $keepMessageBodyInMemory = false
    ): RawTemplatedEmail
    {
        $this->resolveTemplating();

        return $this->templating->create(
            $this->templating->getTemplate($template->value),
            $this->templating->getBody($messageFilepath, $keepMessageBodyInMemory),
            $subject,
            $preHeader
        );
    }

    /**
     * @param Message $message
     * @param string $recipient
     * @param MailDispatchMode|null $policy
     * @return void
     * @throws EmailDeliveryException
     * @throws EmailServiceException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     * @throws \Charcoal\Mailer\Exceptions\EmailComposeException
     * @api
     */
    public function send(
        Message           $message,
        string            $recipient,
        ?MailDispatchMode $policy = null,
    ): void
    {
        $this->sendCompiled($message->compile(), $message->subject, $recipient, $message->sender, $policy);
    }

    /**
     * @throws EmailDeliveryException
     * @throws EmailServiceException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     * @api
     */
    public function sendCompiled(
        CompiledMimeMessage $message,
        string              $subject,
        string              $recipient,
        Sender              $sender,
        ?MailDispatchMode   $mode = null,
    ): void
    {
        $this->resolveClient();

        if (strlen($message->compiledMimeBody) > (5 * 1048576)) {
            throw new EmailServiceException("Compiled MIME length exceeds hard-limit of 5MB");
        }

        $mode = $mode ?? $this->config->mode;
        if ($mode === MailDispatchMode::SEND_ONLY) {
            $this->dispatchCompiled($message, $recipient);
            return;
        }

        $mailStatus = QueuedEmailStatus::PENDING;
        if ($mode === MailDispatchMode::SEND_AND_QUEUE) {
            try {
                $this->dispatchCompiled($message, $recipient);
                $mailStatus = QueuedEmailStatus::SENT;
            } catch (EmailDeliveryException $e) {
                $mailStatus = QueuedEmailStatus::RETRYING;
                $dispatchError = $e->getPrevious() ?
                    ErrorHelper::Exception2String($e->getPrevious()) : $e->getMessage();
            }
        }

        $this->module->backlog->createQueuedEmail(
            $sender->email,
            $recipient,
            $subject,
            $mailStatus === QueuedEmailStatus::SENT ? null : $message,
            $mailStatus,
            $dispatchError ?? null
        );
    }

    /**
     * @param CompiledMimeMessage $message
     * @param string $recipient
     * @return void
     * @throws EmailDeliveryException
     * @api
     */
    private function dispatchCompiled(CompiledMimeMessage $message, string $recipient): void
    {
        try {
            $this->client->agent->send($message, [$recipient]);
        } catch (\Throwable $t) {
            throw EmailDeliveryException::fromAgentContext($this->client->agent, $t);
        }
    }

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     * @throws \Charcoal\Mailer\Exceptions\TemplatingException
     */
    protected function resolveTemplating(): void
    {
        if ($this->templating) {
            return;
        }

        $this->resolveClient();
        $emailsDirectory = $this->module->app->paths->emails;
        $this->templating = new TemplatingEngine($this->client,
            MailerTemplatingSetup::declareMessagesDirectory($emailsDirectory));

        MailerTemplatingSetup::templatingSetup($this->templating);
        foreach (EmailTemplate::cases() as $emailTemplate) {
            if ($emailTemplate->registerInTemplatingEngine()) {
                $this->templating->registerTemplate($emailTemplate->getTemplateFile($emailsDirectory));
            }
        }
    }

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Base\Exceptions\WrappedException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     */
    protected function resolveClient(): void
    {
        if ($this->client) {
            return;
        }

        $this->resolveConfig();
        $this->client = new CharcoalMailer(new Sender($this->config->senderEmail, $this->config->senderName));
        $this->client->agent = MailerAgentResolver::resolveProvider($this->module->app, $this->config);
    }

    /**
     * @return void
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     */
    protected function resolveConfig(): void
    {
        if ($this->config) {
            return;
        }

        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->config = $this->resolveConfigObject(
            $this->module->app,
            MailerConfig::class,
            useStatic: false,
            useObjectStore: true
        );
    }

    /**
     * @param CharcoalApp $app
     * @return MailerConfig|null
     */
    protected function resolveStaticConfig(CharcoalApp $app): ?MailerConfig
    {
        throw new \RuntimeException("Static config is not supported for MailerService");
    }
}