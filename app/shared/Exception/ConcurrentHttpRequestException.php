<?php
declare(strict_types=1);

namespace App\Shared\Exception;

use App\Shared\Core\Http\Policy\Concurrency\ConcurrencyScope;

/**
 * Class ConcurrentHttpRequestException
 * @package App\Shared\Exception
 */
class ConcurrentHttpRequestException extends \Exception
{
    public function __construct(
        public readonly ConcurrencyScope $policy,
        public readonly string           $semaphoreLockKey,
    )
    {
        parent::__construct("", 0, null);
    }
}