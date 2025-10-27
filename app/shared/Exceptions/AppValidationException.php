<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Charcoal\Contracts\Sapi\DomainMessageEnumInterface;
use Charcoal\Contracts\Sapi\SapiRequestContextInterface;
use Charcoal\Contracts\Sapi\ValidationExceptionInterface;

/**
 * Class AppValidationException
 * @package App\Shared\Exceptions
 */
final class AppValidationException extends AppException implements ValidationExceptionInterface
{
    public function __construct(
        DomainMessageEnumInterface   $msg,
        ?array                       $context = null,
        ?SapiRequestContextInterface $requestContext = null,
        ?\Throwable                  $previous = null,
    )
    {
        $code = $msg->getCode($requestContext, $context);
        parent::__construct(
            $msg->getTranslatedMessage($requestContext, $context),
            is_int($code) ? $code : 0,
            $previous,
        );
    }

    /**
     * Returns the translated message as string.
     */
    public function getTranslatedMessage(): string
    {
        return $this->message;
    }

    /**
     * Returns translated exception code as int.
     */
    public function getTranslatedCode(): int
    {
        return $this->code;
    }
}