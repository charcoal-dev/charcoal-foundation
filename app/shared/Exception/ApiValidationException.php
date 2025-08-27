<?php
declare(strict_types=1);

namespace App\Shared\Exception;

use App\Shared\Core\Http\Api\Error\ApiTranslatedErrorInterface;

/**
 * Class ApiValidationException
 * @package App\Shared\Exception
 */
class ApiValidationException extends \Exception
{
    public readonly ?ApiTranslatedErrorInterface $errorCode;
    public readonly ?string $param;

    /**
     * @param string|ApiTranslatedErrorInterface $message
     * @param int $code
     * @param string|null $param
     * @param \Throwable|null $previous
     * @param array|null $baggage
     */
    public function __construct(
        string|ApiTranslatedErrorInterface $message = "",
        int                                $code = 0,
        ?string                            $param = null,
        ?\Throwable                        $previous = null,
        public readonly ?array             $baggage = null
    )
    {
        $errorCode = null;
        if ($message instanceof ApiTranslatedErrorInterface) {
            $errorCode = $message;
            $message = "";
        }

        parent::__construct($message, $code, $previous);

        // Param & errorCode (if any)
        $this->param = $param;
        $this->errorCode = $errorCode;
    }
}