<?php
declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * Class WrappedException
 * @package App\Shared\Exception
 */
class WrappedException extends \Exception
{
    /**
     * @param \Throwable $previous
     * @param string|null $message
     */
    public function __construct(\Throwable $previous, ?string $message = null)
    {
        parent::__construct($message ?? "", 0, $previous);
    }
}