<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Http;

use Charcoal\Http\Client\Security\TlsContext;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\HttpProtocol;

/**
 * This class initializes a default client policy with predefined settings.
 * It provides a method to create a customized `ClientPolicy` instance based
 * on the initialized default configuration.
 */
final readonly class ClientConfig extends \Charcoal\Http\Client\ClientConfig
{
    public function __construct()
    {
        parent::__construct(
            version: HttpProtocol::Version3,
            tlsContext: new TlsContext(),
            authContext: null,
            proxyServer: null,
            userAgent: "Charcoal/Foundation/0.2 (PHP; Linux x86_64)",
            timeout: 3,
            connectTimeout: 3,
            responseContentType: ContentType::Json,
        );
    }
}