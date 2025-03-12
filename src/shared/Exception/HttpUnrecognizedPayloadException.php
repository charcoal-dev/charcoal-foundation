<?php
declare(strict_types=1);

namespace App\Shared\Exception;

/**
 * Class HttpUnrecognizedPayloadException
 * @package App\Shared\Exception
 */
class HttpUnrecognizedPayloadException extends \Exception
{
    public function __construct(string $message, public readonly array $unrecognized)
    {
        parent::__construct($message, 0, null);
    }
}