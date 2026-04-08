<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use Charcoal\App\Kernel\ServerApi\Cli\AppCliHandler;
use Charcoal\App\Kernel\ServerApi\Cli\AppCliScript;

/**
 * Base class for domain-specific scripts, extending the functionality
 * of the AppCliScript class by enforcing time limits and providing utility
 * methods for interacting with the application and error reporting.
 */
abstract class DomainScriptBase extends AppCliScript
{
    use ProcessDomainTrait;

    /**
     * @param AppCliHandler $cli
     * @throws \Charcoal\App\Kernel\Concurrency\ConcurrencyLockException
     */
    public function __construct(AppCliHandler $cli)
    {
        parent::__construct($cli);
        $this->initializeDomainLogic();
    }
}