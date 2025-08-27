<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Persisted;

/**
 * Represents the configuration needed for setting up an SMTP connection.
 * Extends AbstractResolvedConfig and implements PersistedConfigProvidesSnapshot.
 * @api
 */
final class SmtpConfig extends AbstractResolvedConfig
{
    public const string CONFIG_ID = "app.Integrations.Mailer.smtpConfig";

    public ?string $hostname = null;
    public ?string $username = null;
    public ?string $password = null;
    public ?string $domain = null;
    public bool $encryption = true;
    public ?int $port = 587;
    public int $timeout = 1;
}