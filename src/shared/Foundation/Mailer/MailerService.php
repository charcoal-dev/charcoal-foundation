<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use App\Shared\CharcoalApp;
use App\Shared\Core\Config\AbstractComponentConfig;
use App\Shared\Core\Config\ComponentConfigResolverTrait;
use App\Shared\Exception\EmailDeliveryException;
use App\Shared\Exception\EmailServiceException;
use App\Shared\Foundation\Mailer\Backlog\QueuedEmailStatus;
use App\Shared\Foundation\Mailer\Config\MailDispatchMode;
use App\Shared\Foundation\Mailer\Config\MailerConfig;
use Charcoal\App\Kernel\Errors;
use Charcoal\App\Kernel\Module\AbstractModuleComponent;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;
use Charcoal\Mailer\Mailer as CharcoalMailer;
use Charcoal\Mailer\Message\Sender;
use Charcoal\Mailer\Templating\RawTemplatedEmail;
use Charcoal\Mailer\TemplatingEngine;

/**
 * Class MailerService
 * @package App\Shared\Foundation\Mailer
 * @property MailerModule $module
 */
class MailerService extends AbstractModuleComponent
{
    public readonly bool $hasBacklog;
    private ?MailerConfig $config = null;
    private ?CharcoalMailer $client = null;
    private ?TemplatingEngine $templating = null;

    use ComponentConfigResolverTrait;

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
        $data["config"] = null;
        $data["client"] = null;
        $data["templating"] = null;
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
        $this->config = null;
        $this->client = null;
        $this->templating = null;
    }

    /**
     * @param string $messageFilepath
     * @param string $subject
     * @param string|null $preHeader
     * @param EmailTemplate $template
     * @param bool $keepMessageBodyInMemory
     * @return RawTemplatedEmail
     * @throws \Charcoal\Mailer\Exception\DataBindException
     * @throws \Charcoal\Mailer\Exception\TemplatingException
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
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     * @throws \Charcoal\Mailer\Exception\EmailComposeException
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
     * @param CompiledMimeMessage $message
     * @param string $subject
     * @param string $recipient
     * @param Sender $sender
     * @param MailDispatchMode|null $policy
     * @return void
     * @throws EmailDeliveryException
     * @throws EmailServiceException
     * @throws \Charcoal\App\Kernel\Orm\Exception\EntityOrmException
     */
    public function sendCompiled(
        CompiledMimeMessage $message,
        string              $subject,
        string              $recipient,
        Sender              $sender,
        ?MailDispatchMode   $policy = null,
    ): void
    {
        $this->resolveClient();

        if (strlen($message->compiledMimeBody) > (5 * 1048576)) {
            throw new EmailServiceException("Compiled MIME length exceeds hard-limit of 5MB");
        }

        $policy = $policy ?? $this->config->policy;
        if ($policy === MailDispatchMode::SEND_ONLY) {
            $this->dispatchCompiled($message, $recipient);
            return;
        }

        if (!$this->hasBacklog) {
            throw new EmailServiceException("Application build does not have mailer backlog");
        }

        $mailStatus = QueuedEmailStatus::PENDING;
        if ($policy === MailDispatchMode::SEND_AND_QUEUE) {
            try {
                $this->dispatchCompiled($message, $recipient);
                $mailStatus = QueuedEmailStatus::SENT;
            } catch (EmailDeliveryException $e) {
                $mailStatus = QueuedEmailStatus::RETRYING;
                $dispatchError = $e->getPrevious() ? Errors::Exception2String($e->getPrevious()) : $e->getMessage();
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
     * @throws \Charcoal\Mailer\Exception\DataBindException
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    protected function resolveTemplating(): void
    {
        if ($this->templating) {
            return;
        }

        $this->resolveClient();
        $emailsDirectory = $this->module->app->directories->emails;
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
     */
    protected function resolveClient(): void
    {
        if ($this->client) {
            return;
        }

        $this->resolveConfig();
        $this->client = new CharcoalMailer(new Sender($this->config->senderEmail, $this->config->senderName));
        $this->client->agent = MailerAgentResolver::resolveProvider($this->config);
    }

    /**
     * @return void
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
            useStatic: true,
            useObjectStore: false
        );
    }

    /**
     * @param CharcoalApp $app
     * @return AbstractComponentConfig|null
     */
    protected function resolveStaticConfig(CharcoalApp $app): ?AbstractComponentConfig
    {
        if (isset($app->config->mailer)) {
            return $app->config->mailer;
        }

        return null;
    }
}