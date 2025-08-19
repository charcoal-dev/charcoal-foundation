<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Http;

use App\Shared\Core\Config\Http\Router\Inbound;
use App\Shared\Core\Config\Http\Router\Outbound;
use App\Shared\Core\Http\RouterLogger;
use Charcoal\Http\Router\Policy\RouterPolicy;

/**
 * This class extends the RouterPolicy and provides a specific configuration
 * for inbound and outbound headers and payload handling, using the
 * RouterLogger for logging purposes.
 */
final readonly class RouterConfig extends RouterPolicy
{
    public function __construct()
    {
        parent::__construct(
            Inbound::headersConfig(),
            Inbound::payloadConfig(),
            Outbound::headersConfig(),
            Outbound::payloadConfig(),
            new RouterLogger(),
            parsePayloadKeepBody: false,
            parsePayloadUndefinedParam: "json"
        );
    }
}