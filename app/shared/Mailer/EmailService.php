<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Mailer;

use App\Shared\AppConstants;
use App\Shared\CharcoalApp;
use App\Shared\Config\Snapshot\MailerConfig;
use App\Shared\Contracts\EmailMessageInterface;
use App\Shared\Enums\MailProvider;
use App\Shared\Exceptions\AppTranslatedException;
use App\Shared\Mailer\Enums\MailDispatchPolicy;
use App\Shared\Mailer\MailsQueue\MailQueueEntity;
use Charcoal\App\Kernel\Clock\Clock;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\App\Kernel\Entity\Exceptions\ChecksumComputeException;
use Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException;
use Charcoal\Base\Objects\Traits\NotCloneableTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Mailer\Exceptions\EmailComposeException;
use Charcoal\Mailer\Exceptions\TemplatingException;
use Charcoal\Mailer\Mailer;
use Charcoal\Mailer\Message;
use Charcoal\Mailer\Message\CompiledMimeMessage;
use Charcoal\Mailer\Message\Sender;
use Charcoal\Mailer\Templating\RawTemplatedEmail;
use Charcoal\Mailer\TemplatingEngine;

/**
 * Handles the preparation, dispatch, and sending of email messages using configured mailer agents and templating.
 * Includes capabilities for queueing emails, enforcing dispatch policies, and ensuring MIME compliance.
 */
final readonly class EmailService
{
    use NotSerializableTrait;
    use NotCloneableTrait;

    public MailerConfig $mailerConfig;
    public Mailer $mailerAgent;
    public TemplatingEngine $templating;

    /**
     * @param CharcoalApp $app
     */
    public function __construct(protected CharcoalApp $app)
    {
        $this->mailerConfig = MailerConfigResolver::getMailerConfig($this->app);

        // Mailer Agent/Provider
        $this->mailerAgent = new Mailer(
            new Sender($this->mailerConfig->senderEmail, $this->mailerConfig->senderName),
            $this->mailerConfig->agent->getAgent($this->app)
        );

        // MIME Signatures Setup
        $this->mailerAgent->clientConfig->name = AppConstants::MAILER_SIGNATURE;
        $this->mailerAgent->clientConfig->boundary1Prefix = AppConstants::MAILER_BOUNDARY_PREFIX . "1";
        $this->mailerAgent->clientConfig->boundary2Prefix = AppConstants::MAILER_BOUNDARY_PREFIX . "2";
        $this->mailerAgent->clientConfig->boundary3Prefix = AppConstants::MAILER_BOUNDARY_PREFIX . "3";

        // Templating
        $this->templating = new TemplatingEngine($this->mailerAgent,
            $this->app->paths->emails->absolute . DIRECTORY_SEPARATOR . "messages");
        $this->templating->modifiers->registerDefaultModifiers();
        $this->templating->set("now", Clock::getTimestamp());
    }

    /**
     * Prepare the email message for sending.
     * @param EmailMessageInterface $email
     * @return RawTemplatedEmail
     */
    public function prepare(EmailMessageInterface $email): RawTemplatedEmail
    {
        try {
            return $this->templating->create(
                $email->getWrapper(),
                $this->templating->getBody($email->getBodyFile(), runtimeMemory: true),
                $email->getSubject(),
                $email->getPreHeader()
            );
        } catch (TemplatingException $e) {
            throw new \RuntimeException("Failed to prepare email message", previous: $e);
        }
    }

    /**
     * @throws AppTranslatedException
     * @throws EmailComposeException
     */
    public function dispatch(CompiledMimeMessage|Message $message, string $recipient): bool
    {
        $message = $this->ensureCompiledMimeFormat($message);
        return $this->_dispatch($message, $recipient);
    }

    /**
     * @throws EmailComposeException
     */
    private function _dispatch(CompiledMimeMessage $message, string $recipient): bool
    {
        if ($this->mailerConfig->agent === MailProvider::Disabled) {
            return false;
        }

        $this->mailerAgent->send($message, $recipient);
        return true;
    }

    /**
     * @throws AppTranslatedException
     * @throws EmailComposeException
     * @throws ChecksumComputeException
     * @throws EntityRepositoryException
     */
    public function send(
        CompiledMimeMessage|Message $message,
        string                      $recipient,
        ?MailDispatchPolicy         $policy = null,
        ?\DateTimeImmutable         $timestamp = null,
        bool                        $noRetries = false
    ): bool|MailQueueEntity
    {
        $timestamp = $timestamp ?? Clock::now();
        $policy = $policy ?? $this->mailerConfig->policy;
        if ($this->mailerConfig->agent === MailProvider::Disabled) {
            $policy = MailDispatchPolicy::Queue_Only;
        }

        $message = $this->ensureCompiledMimeFormat($message);
        if ($policy === MailDispatchPolicy::Send_Only) {
            try {
                $this->_dispatch($message, $recipient);
            } catch (\Exception $e) {
                Diagnostics::app()->warning("Failed to dispatch email", exception: $e);
                throw $e;
            }

            return true;
        }

        if ($policy === MailDispatchPolicy::Queue_Only) {
            return $this->app->mailer->queue->addToQueue($message,
                $recipient, $timestamp);
        }

        // MailDispatchPolicy::Send_And_Log
        try {
            $this->_dispatch($message, $recipient);
        } catch (\Exception $e) {
            Diagnostics::app()->warning("Failed to dispatch email", exception: $e);
            return $this->app->mailer->queue->logError($message, $recipient, $timestamp, $e, $noRetries);
        }

        return $this->app->mailer->queue->logSent($message, $recipient, $timestamp);
    }

    /**
     * @throws AppTranslatedException
     * @throws EmailComposeException
     */
    private function ensureCompiledMimeFormat(CompiledMimeMessage|Message $message): CompiledMimeMessage
    {
        if (!$message instanceof CompiledMimeMessage) {
            $message = $message->compile();
        }

        if (strlen($message->compiledMimeBody) > (5 * 1048576)) {
            throw new AppTranslatedException("Compiled MIME length exceeds hard-limit of 5MB");
        }

        return $message;
    }
}