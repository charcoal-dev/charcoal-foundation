<?php
declare(strict_types=1);

namespace App\Shared;

use App\Shared\Core\Db\Databases;
use App\Shared\Core\Directories;
use App\Shared\Core\Events;
use Charcoal\App\Kernel\AppKernel;
use Charcoal\App\Kernel\Config;
use Charcoal\App\Kernel\Errors\FileErrorLogger;
use Charcoal\Filesystem\Directory;

/**
 * Class CharcoalApp
 * @package App\Shared
 * @property Databases $databases
 * @property Directories $directories
 */
class CharcoalApp extends AppKernel
{
    protected const string ERROR_LOG_FILE = "log/error.log";

    /**
     * @param Directory $rootDirectory
     * @param string $configClass
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(
        Directory        $rootDirectory,
        protected string $configClass = \App\Shared\Core\Config::class
    )
    {
        parent::__construct(
            $rootDirectory,
            new FileErrorLogger($rootDirectory->getFile(static::ERROR_LOG_FILE, true), useAnsiEscapeSeq: true),
            Directories::class,
            Events::class,
            Databases::class,
        );
    }

    /**
     * @return Config
     */
    protected function renderConfig(): Config
    {
        return new ($this->configClass)($this->directories->config);
    }
}