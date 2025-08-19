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
final readonly class ClientConfig
{
    private ?ClientPolicy $defaultPolicy;

    public function __construct()
    {
        $this->defaultPolicy = new ClientPolicy();
        $this->defaultPolicy->version = Http::Version3;
        $this->defaultPolicy->tlsContext = new TlsContext();
        $this->defaultPolicy->authContext = null;
        $this->defaultPolicy->proxyServer = null;
        $this->defaultPolicy->userAgent = "Charcoal/Foundation-App";
        $this->defaultPolicy->timeout = 3;
        $this->defaultPolicy->connectTimeout = 3;
        $this->defaultPolicy->responseContentType = ContentType::Json;
        $this->defaultPolicy->requestHeaders = Request::headersConfig();
        $this->defaultPolicy->requestPayload = Request::payloadConfig();
        $this->defaultPolicy->responseHeaders = Response::headersConfig();
        $this->defaultPolicy->responsePayload = Response::payloadConfig();
    }

    public function createClientConfig(): ClientPolicy
    {
        return new ClientPolicy($this->defaultPolicy);
    }
}