<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

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
    public DirectoryPath $tmp;
    public DirectoryPath $log;
    public DirectoryPath $shared;
    public DirectoryPath $semaphore;
    public DirectoryPath $storage;
    public DirectoryPath $emails;

    /**
     * Declaring paths right in application constructor (no lazy loading)
     */
    public function declarePaths(): void
    {
        $this->log = $this->getValidatedPathSnapshot("/log", true, true, true, true);
        $this->config = $this->getValidatedPathSnapshot("/config", true, true, false, true);
        $this->tmp = $this->getValidatedPathSnapshot("/tmp", true, true, true, true);
        $this->shared = $this->getValidatedPathSnapshot("/shared", true, true, true, true);
        $this->storage = $this->getValidatedPathSnapshot("/storage", true, true, false, true);
    }
}