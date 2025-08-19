<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Http;

use App\Shared\Core\Config\Http\Client\Request;
use App\Shared\Core\Config\Http\Client\Response;
use Charcoal\Http\Client\Policy\ClientPolicy;
use Charcoal\Http\Client\Security\TlsContext;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Commons\Enums\Http;

/**
 * This class initializes a default client policy with predefined settings.
 * It provides a method to create a customized `ClientPolicy` instance based
 * on the initialized default configuration.
 */
final readonly class ClientConfig extends ClientPolicy
{
    public function __construct()
    {
        parent::__construct(
            null,
            version: Http::Version3,
            tlsContext: new TlsContext(),
            authContext: null,
            proxyServer: null,
            userAgent: "Charcoal/Foundation-App",
            timeout: 3,
            connectTimeout: 3,
            responseContentType: ContentType::Json,
            requestHeaders: Request::headersConfig(),
            responseHeaders: Response::headersConfig(),
            requestPayload: Request::payloadConfig(),
            responsePayload: Response::payloadConfig(),
        );
    }
}