<?php
declare(strict_types=1);

namespace App\Shared;

use App\Shared\Core\Db\Databases;
use App\Shared\Core\Directories;
use App\Shared\Core\Events;
use Charcoal\App\Kernel\AppBuild;
use Charcoal\App\Kernel\Config;
use Charcoal\App\Kernel\Errors\FileErrorLogger;
use Charcoal\Filesystem\Directory;

/**
 * Class CharcoalApp
 * @package App\Shared
 * @property Databases $databases
 * @property Directories $directories
 * @property Events $events
 */
abstract class CharcoalApp extends AppBuild
{
    /**
     * @param BuildContext $context
     * @param Directory $rootDirectory
     * @param string $configClass
     * @param string $errorLogFilepath
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     */
    public function __construct(
        BuildContext     $context,
        Directory        $rootDirectory,
        string           $errorLogFilepath = "./log/error.log",
        protected string $configClass = \App\Shared\Core\Config::class,
    )
    {
        parent::__construct(
            $context,
            $rootDirectory,
            new FileErrorLogger($rootDirectory->getFile($errorLogFilepath, true), useAnsiEscapeSeq: true),
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