<?php
declare(strict_types=1);

namespace App\Shared;

use App\Shared\Core\Db\Databases;
use App\Shared\Core\Directories;
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
    public function __construct(Directory $rootDirectory)
    {
        $errorLog = new FileErrorLogger($rootDirectory->getFile("log/error.log", true));
        $errorLog->useAnsiEscapeSeq = true;

        parent::__construct(
            $rootDirectory,
            $errorLog,
            Directories::class,
        );
    }

    protected function renderConfig(): Config
    {
    }
}