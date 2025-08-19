<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Persisted;

use App\Shared\Enums\Http\HttpLogLevel;

/**
 * Represents the configuration of an HTTP interface, providing options to control logging,
 * tracing, and caching behavior.
 */
class HttpInterfaceConfig extends AbstractResolvedConfig
{
    public bool $status;
    public HttpLogLevel $logData;
    public bool $logHttpMethodOptions;
    public ?string $traceHeader;
    public ?string $cachedResponseHeader;
}