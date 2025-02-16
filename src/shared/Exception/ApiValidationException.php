<?php
declare(strict_types=1);

namespace App\Shared\Exception;

use App\Shared\Utility\StringHelper;

/**
 * Class ApiValidationException
 * @package App\Shared\Exception
 */
class ApiValidationException extends \Exception
{
    public readonly ?string $param;
    public readonly ?array $baggage;
    public readonly ?string $errorCode;

    /**
     * @param string $message
     * @param int $code
     * @param string|null $param
     * @param \StringBackedEnum|string|null $errorCode
     * @param \Throwable|null $previous
     */
    public function __construct(
        string                        $message = "",
        int                           $code = 0,
        ?string                       $param = null,
        \StringBackedEnum|string|null $errorCode = null,
        ?\Throwable                   $previous = null
    )
    {
        parent::__construct($message, $code, $previous);

        // Param & errorCode (if any)
        $this->param = $param;
        $this->errorCode = StringHelper::getTrimmedOrNull($errorCode) ?
            ($errorCode instanceof \StringBackedEnum ? $errorCode->value : $errorCode) : null;
    }
}