<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\Config;

use App\Shared\Core\Config\AbstractComponentConfig;
use App\Shared\Foundation\Http\HttpLogLevel;

/**
 * Class AbstractHttpInterfaceConfig
 * @package App\Shared\Foundation\Http\Config
 */
class HttpInterfaceConfig extends AbstractComponentConfig
{
    public bool $status;
    public HttpLogLevel $logData;
    public bool $logHttpMethodOptions;
    public ?string $traceHeader;
    public ?string $cachedResponseHeader;
}