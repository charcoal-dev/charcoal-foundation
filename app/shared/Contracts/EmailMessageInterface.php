<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Contracts;

use Charcoal\Mailer\Templating\EmailTemplateFile;

/**
 * Interface EmailMessageInterface
 */
interface EmailMessageInterface
{
    public function getSubject(): string;

    public function getPreHeader(): ?string;

    public function getBodyFile(): string;

    public function getWrapper(): EmailTemplateFile;
}