<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Cli;

use Charcoal\App\Kernel\ServerApi\Cli\AppCliHandler;
use Charcoal\App\Kernel\ServerApi\Cli\AppCliProcess;

/**
 * An abstract class that represents a domain-specific business process.
 */
abstract class DomainProcessBase extends AppCliProcess
{
    use ConsoleExecutionTrait;

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