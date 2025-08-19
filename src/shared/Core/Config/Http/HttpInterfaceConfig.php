<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Http;

use App\Shared\Core\Config\Persisted\AbstractResolvedConfig;
use App\Shared\Enums\Http\HttpLogLevel;

/**
 * Class AbstractHttpInterfaceConfig
 * @package App\Shared\Foundation\Http\Config
 */
class HttpInterfaceConfig extends AbstractResolvedConfig
{
    public bool $status;
    public HttpLogLevel $logData;
    public bool $logHttpMethodOptions;
    public ?string $traceHeader;
    public ?string $cachedResponseHeader;
}