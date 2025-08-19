<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Http\Router;

use App\Shared\Contracts\Config\HttpPolicyFactoryInterface;
use Charcoal\Base\Enums\Charset;
use Charcoal\Base\Enums\ValidationState;
use Charcoal\Http\Commons\Enums\HeaderKeyPolicy;
use Charcoal\Http\Commons\Enums\ParamKeyPolicy;
use Charcoal\Http\Router\Policy\HeadersPolicy;
use Charcoal\Http\Router\Policy\PayloadPolicy;

/**
 * Represents an outgoing response and defines configuration policies for handling
 * request headers and payloads.
 */
final readonly class Outbound implements HttpPolicyFactoryInterface
{
    public static function headersConfig(): HeadersPolicy
    {
        return new HeadersPolicy(
            keyPolicy: HeaderKeyPolicy::RFC7230,
            keyMaxLength: 64,
            keyOverflowTrim: false,
            valueMaxLength: 256,
            valueOverflowTrim: false,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::VALIDATED,
            valueTrust: ValidationState::TRUSTED,
        );
    }

    public static function payloadConfig(): PayloadPolicy
    {
        return new PayloadPolicy(
            keyPolicy: ParamKeyPolicy::UNSANITIZED,
            charset: Charset::UTF8,
            keyMaxLength: 64,
            keyOverflowTrim: false,
            valueMaxLength: 2048,
            valueOverflowTrim: false,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::VALIDATED,
            valueTrust: ValidationState::TRUSTED,
        );
    }
}