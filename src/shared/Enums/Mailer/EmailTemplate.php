<?php
declare(strict_types=1);

namespace App\Shared\Enums\Mailer;

use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Filesystem\Path\FilePath;
use Charcoal\Mailer\Exceptions\TemplatingException;
use Charcoal\Mailer\Templating\EmailTemplateFile;

/**
 * Enumeration representing email templates.
 */
enum EmailTemplate: string
{
    case DEFAULT = "default";

    /**
     * @return bool
     */
    public function registerInTemplatingEngine(): bool
    {
        return match ($this) {
            self::DEFAULT => true,
        };
    }

    /**
     * @param DirectoryPath $emailsDirectory
     * @return EmailTemplateFile
     * @throws TemplatingException
     */
    public function getTemplateFile(DirectoryPath $emailsDirectory): EmailTemplateFile
    {
        try {
            $template = new FilePath($emailsDirectory->absolute . "/templates/" . $this->value . ".html");
        } catch (\Exception $e) {
            throw new TemplatingException(sprintf('Template file "%s" is not readable', $this->value), previous: $e);
        }

        return new EmailTemplateFile($this->value, $template->absolute);
    }
}