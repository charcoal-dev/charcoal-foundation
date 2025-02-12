<?php
declare(strict_types=1);

namespace App\Shared;

use App\Shared\Core\Cache\CachePool;
use App\Shared\Core\Config;
use App\Shared\Core\Db\Databases;
use App\Shared\Core\Directories;
use App\Shared\Core\Events;
use App\Shared\Foundation\CoreData\CoreDataModule;
use App\Shared\Foundation\CoreData\SystemAlerts\SystemAlertEntity;
use App\Shared\Foundation\Engine\EngineModule;
use App\Shared\Foundation\Http\HttpModule;
use App\Shared\Foundation\Mailer\MailerModule;
use Charcoal\App\Kernel\AppBuild;
use Charcoal\App\Kernel\Errors\FileErrorLogger;
use Charcoal\Cipher\Cipher;
use Charcoal\Filesystem\Directory;
use Charcoal\Semaphore\FilesystemSemaphore;

/**
 * Class CharcoalApp
 * @package App\Shared
 * @property CachePool $cache
 * @property Databases $databases
 * @property Directories $directories
 * @property Events $events
 * @property Config $config
 */
class CharcoalApp extends AppBuild
{
    public readonly FilesystemSemaphore $semaphore;

    public CoreDataModule $coreData;
    public HttpModule $http;
    public MailerModule $mailer;
    public EngineModule $engine;

    /**
     * @param BuildContext $context
     * @param Directory $rootDirectory
     * @param string $errorLogFilepath
     * @param string $configClass
     * @throws \Charcoal\Filesystem\Exception\FilesystemException
     * @throws \Charcoal\Semaphore\Exception\SemaphoreException
     */
    public function __construct(
        BuildContext     $context,
        Directory        $rootDirectory,
        string           $errorLogFilepath = "./log/error.log",
        protected string $configClass = \App\Shared\Core\Config::class,
    )
    {
        $this->semaphore = new FilesystemSemaphore($this->directories->semaphore);

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
        /** @var Config $config */
        $config = new ($this->configClass)($this->directories);

        // Create Ciphers from Configuration
        foreach ($config->ciphers->keychain as $cipherId => $cipherObj) {
            $this->cipher->set($cipherId, new Cipher($cipherObj["entropy"], $cipherObj["mode"]));
        }

        return $config;
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
     * @return array
     */
    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["semaphore"] = $this->semaphore;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->semaphore = $data["semaphore"];
    }

    /**
     * @param SystemAlertEntity $alert
     * @return void
     */
    public function onSystemAlert(SystemAlertEntity $alert): void
    {
    }
}