<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Telemetry\HttpIngress;

use App\Shared\Enums\Interfaces;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;

/**
 * Represents an HTTP ingress log entity.
 * Extends the OrmEntityBase class and encapsulates the details of an HTTP request/response interaction.
 */
final class HttpIngressLogEntity extends OrmEntityBase
{
    public int $id;
    public Interfaces $interface;
    public string $uuid;
    public string $ipAddress;
    public ?int $responseCode;
    public string $method;
    public string $urlScheme;
    public string $urlHost;
    public ?int $urlPort;
    public string $urlPath;
    public ?string $controller;
    public ?string $entrypoint;
    public ?string $requestHeaders;
    public ?string $requestParamsQuery;
    public ?string $requestParamsBody;
    public ?string $responseHeaders;
    public ?string $responseParams;
    public ?string $responseCachedId;
    public bool $hasMetrics = false;
    public bool $hasLogs = false;
    public int $loggedAt;
    public ?int $duration;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }
}