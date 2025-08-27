<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use Charcoal\Filesystem\Directory;
use Charcoal\Mailer\Templating\EmailTemplateFile;

/**
 * Class EmailTemplate
 * @package App\Shared\Foundation\Mailer
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
     * @param Directory $emailsDirectory
     * @return EmailTemplateFile
     * @throws \Charcoal\Mailer\Exception\TemplatingException
     */
    public function getTemplateFile(Directory $emailsDirectory): EmailTemplateFile
    {
        return new EmailTemplateFile($this->value,
            $emailsDirectory->pathToChild("./templates/" . $this->value . ".html", validations: false));
    }
}