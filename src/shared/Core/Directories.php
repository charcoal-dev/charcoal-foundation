<?php
declare(strict_types=1);

namespace App\Shared\Core;

use Charcoal\Filesystem\Directory;

/**
 * Class Directories
 * @package App\Shared\Core
 */
class Directories extends \Charcoal\App\Kernel\Directories
{
    public readonly Directory $config;
    public readonly Directory $log;
    public readonly Directory $semaphore;
    public readonly Directory $storage;
    public readonly Directory $tmp;

    public function __construct(Directory $root)
    {
        parent::__construct($root);
        $this->config = $this->validateDirectory("/config", true, false);
        $this->log = $this->validateDirectory("/log", true, true);
        $this->tmp = $this->validateDirectory("/tmp", true, true);
        $this->semaphore = $this->validateDirectory("/tmp/semaphore", true, true);
        $this->storage = $this->validateDirectory("/storage", true, true);
    }
}