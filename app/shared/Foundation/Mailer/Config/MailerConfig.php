<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer\Config;

use App\Shared\Core\Config\AbstractComponentConfig;
use Charcoal\OOP\Traits\NoDumpTrait;

/**
 * Class MailerConfig
 * @package App\Shared\Foundation\Mailer\Config
 */
class MailerConfig extends AbstractComponentConfig
{
    public const string CONFIG_ID = "app.Foundation.MailerConfig";

    public MailProvider $service = MailProvider::DISABLED;
    public MailDispatchMode $policy = MailDispatchMode::SEND_ONLY;
    public string $senderName;
    public string $senderEmail;

    public bool $queueProcessing = false;
    public int $queueRetryTimeout = 300;
    public int $queueExhaustAfter = 10;
    public int $queueTickInterval = 1;

    public ?string $smtpHostname = null;
    public ?string $smtpUsername = null;
    public ?string $smtpPassword = null;
    public ?string $smtpDomain = null;
    public bool $smtpEncryption = true;
    public ?int $smtpPort = 587;
    public int $smtpTimeout = 1;

    public ?string $apiKey = null;
    public ?string $apiDomain = null;
    public ?string $apiServerRegion = null;
    public int $apiTimeout = 1;
    public int $apiConnectTimeout = 1;

    use NoDumpTrait;
}