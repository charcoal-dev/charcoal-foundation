<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Exceptions;

use Charcoal\Contracts\Sapi\DomainMessageEnumInterface;
use Charcoal\Contracts\Sapi\Exceptions\TranslatedExceptionInterface;
use Charcoal\Contracts\Sapi\SapiRequestContextInterface;

/**
 * Represents an exception with support for translation and additional context.
 * Extends the base AppException and implements the TranslatedExceptionInterface.
 * This exception enables translation of messages and codes while providing enhanced
 * contextual information to the exception subsystem.
 */
final class AppTranslatedException extends AppException implements TranslatedExceptionInterface
{
    public readonly array $context;

    public function __construct(
        DomainMessageEnumInterface|string $msg,
        array                             $context = [],
        ?array                            $words = null,
        ?SapiRequestContextInterface      $requestContext = null,
        ?\Throwable                       $previous = null,
        ?int                              $code = null,
        ?string                           $param = null
    )
    {
        if ($param) {
            $context["param"] = $param;
        }

        $this->context = $context;
        if (!$code && $msg instanceof DomainMessageEnumInterface) {
            $code = $msg->getCode($requestContext, $context);
        }

        parent::__construct(
            $msg instanceof DomainMessageEnumInterface ?
                $msg->getTranslated($requestContext, $words ?? []) : $msg,
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

    /**
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}