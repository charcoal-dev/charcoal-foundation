<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Http\Client;

use App\Shared\Contracts\Config\HttpPolicyFactoryInterface;
use Charcoal\Base\Enums\Charset;
use Charcoal\Base\Enums\ValidationState;
use Charcoal\Http\Client\Policy\HeadersPolicy;
use Charcoal\Http\Client\Policy\PayloadPolicy;
use Charcoal\Http\Commons\Enums\HeaderKeyPolicy;
use Charcoal\Http\Commons\Enums\ParamKeyPolicy;

/**
 * Represents an incoming response and defines configuration policies for handling
 * request headers and payloads.
 */
final readonly class Response implements HttpPolicyFactoryInterface
{
    public static function headersConfig(): HeadersPolicy
    {
        return new HeadersPolicy(
            keyPolicy: HeaderKeyPolicy::STRICT,
            keyMaxLength: 64,
            keyOverflowTrim: false,
            valueMaxLength: 256,
            valueOverflowTrim: false,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::RAW,
            valueTrust: ValidationState::RAW,
        );
    }

    public static function payloadConfig(): PayloadPolicy
    {
        return new PayloadPolicy(
            keyPolicy: ParamKeyPolicy::STRICT,
            charset: Charset::UTF8,
            keyMaxLength: 64,
            keyOverflowTrim: false,
            valueMaxLength: 1024,
            valueOverflowTrim: false,
            accessKeyTrust: ValidationState::VALIDATED,
            setterKeyTrust: ValidationState::RAW,
            valueTrust: ValidationState::RAW,
        );
    }
}