<?php
declare(strict_types=1);

namespace App\Shared\Core;

use Charcoal\Filesystem\Path\DirectoryPath;

/**
 * This class ensures that specific directories required by the application
 * (e.g., configuration, emails, logging, temporary files, semaphores, and storage)
 * are properly initialized and validated.
 */
final readonly class PathRegistry extends \Charcoal\App\Kernel\Internal\PathRegistry
{
    public DirectoryPath $config;
    public DirectoryPath $emails;
    public DirectoryPath $log;
    public DirectoryPath $storage;
    public DirectoryPath $tmp;

    /**
     * Declaring paths right in application constructor (no lazy loading)
     */
    public function declarePaths(): void
    {
        $this->config = $this->getValidatedPathSnapshot("/config", true, true, false, true);
        $this->log = $this->getValidatedPathSnapshot("/log", true, true, true, true);
        $this->tmp = $this->getValidatedPathSnapshot("/tmp", true, true, true, true);
        $this->storage = $this->getValidatedPathSnapshot("/storage", true, true, false, true);
        $this->emails = $this->getValidatedPathSnapshot("/emails", true, true, false, true);
    }
}