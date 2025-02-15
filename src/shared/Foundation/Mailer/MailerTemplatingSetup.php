<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use Charcoal\Filesystem\Directory;
use Charcoal\Mailer\Templating\EmailTemplateFile;
use Charcoal\Mailer\TemplatingEngine;

/**
 * Class MailerTemplatingSetup
 * @package App\Shared\Foundation\Mailer
 */
class MailerTemplatingSetup
{
    /**
     * @param Directory $emailsDirectory
     * @return string
     */
    public static function declareMessagesDirectory(Directory $emailsDirectory): string
    {
        return $emailsDirectory->pathToChild("./messages", validations: false);
    }

    /**
     * @param TemplatingEngine $templatingEngine
     * @return void
     * @throws \Charcoal\Mailer\Exception\DataBindException
     */
    public static function templatingSetup(TemplatingEngine $templatingEngine): void
    {
        // Modifiers:
        $templatingEngine->modifiers->registerDefaultModifiers();

        // Default dataset:
        $templatingEngine->set("now", time());
    }
}