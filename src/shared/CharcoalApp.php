<?php
declare(strict_types=1);

namespace App\Shared;

use App\Shared\Core\Cache\CachePool;
use App\Shared\Core\Db\Databases;
use App\Shared\Core\Directories;
use App\Shared\Core\Events;
use App\Shared\Foundation\CoreData\CoreDataModule;
use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertEntity;
use Charcoal\App\Kernel\AppBuild;
use Charcoal\App\Kernel\Config;
use Charcoal\App\Kernel\Errors\FileErrorLogger;
use Charcoal\Filesystem\Directory;

/**
 * Class CharcoalApp
 * @package App\Shared
 * @property CachePool $cache
 * @property Databases $databases
 * @property Directories $directories
 * @property Events $events
 */
class CharcoalApp extends AppBuild
{
    public CoreDataModule $coreData;

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
            CachePool::class,
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

    /**
     * @return string
     */
    public static function getAppClassname(): string
    {
        $appClassname = "\\App\\Shared\\" . getenv("APP_CLASSNAME");
        if (!class_exists($appClassname)) {
            throw new \LogicException(sprintf('No such Charcoal app with name "%s" defined', getenv("APP_CLASSNAME")));
        }

        return $appClassname;
    }

    /**
     * @param SystemAlertEntity $alert
     * @return void
     */
    public function onSystemAlert(SystemAlertEntity $alert): void
    {
    }
}