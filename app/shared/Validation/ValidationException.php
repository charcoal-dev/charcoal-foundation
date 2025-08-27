<?php
declare(strict_types=1);

namespace App\Shared\Validation;

use Charcoal\OOP\OOP;

/**
 * Class ValidationException
 * @package App\Shared\Validation
 */
class ValidationException extends \Exception
{
    /**
     * @param ValidationErrorEnumInterface $errorCode
     * @param \Throwable|null $previous
     */
    public function __construct(
        public readonly ValidationErrorEnumInterface $errorCode,
        ?\Throwable                                  $previous = null
    )
    {
        parent::__construct(sprintf("Encountered %s in %s",
            $this->errorCode->name, OOP::baseClassName(get_class($this->errorCode))), 0, $previous);
    }
}