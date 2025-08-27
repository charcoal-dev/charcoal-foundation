<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Mailer;

use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Mailer\TemplatingEngine;

/**
 * Class MailerTemplatingSetup
 * @package App\Shared\Foundation\Mailer
 */
final class MailerTemplatingSetup
{
    /**
     * @param DirectoryPath $emailsDirectory
     * @return string
     */
    public static function declareMessagesDirectory(DirectoryPath $emailsDirectory): string
    {
        return $emailsDirectory->absolute . "/messages";
    }

    /**
     * @param TemplatingEngine $templatingEngine
     * @return void
     * @throws \Charcoal\Mailer\Exceptions\DataBindException
     */
    public static function templatingSetup(TemplatingEngine $templatingEngine): void
    {
        // Modifiers:
        $templatingEngine->modifiers->registerDefaultModifiers();

        // Default dataset:
        $templatingEngine->set("now", time());
    }
}