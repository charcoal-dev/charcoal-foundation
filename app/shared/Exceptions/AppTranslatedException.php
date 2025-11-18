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
 * Class AppTranslatedException
 * @package App\Shared\Exceptions
 */
final class AppTranslatedException extends AppException implements TranslatedExceptionInterface
{
    public function __construct(
        DomainMessageEnumInterface   $msg,
        public readonly array        $context = [],
        ?SapiRequestContextInterface $requestContext = null,
        ?\Throwable                  $previous = null,
    )
    {
        $code = $msg->getCode($requestContext, $context);
        parent::__construct(
            $msg->getTranslated($requestContext, $context),
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