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
 * Represents an incoming request and defines configuration policies for handling
 * request headers and payloads.
 * @internal
 */
final readonly class Inbound implements HttpPolicyFactoryInterface
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
            keyPolicy: ParamKeyPolicy::REGULAR,
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