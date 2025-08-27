<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\Config;

use App\Shared\Core\Config\AbstractComponentConfig;

/**
 * Class HttpClientConfig
 * @package App\Shared\Foundation\Http\Config
 */
class HttpClientConfig extends AbstractComponentConfig
{
    public const string CONFIG_ID = "app.Foundation.Http.ClientConfig";

    public string $userAgent;
    public string $sslCertificateFilePath;
    public int $timeout;
    public int $connectTimeout;
}