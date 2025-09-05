<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http\ProxyServers;

use App\Shared\Enums\Http\ProxyType;
use Charcoal\App\Kernel\Contracts\Orm\Entity\StorageHooksInterface;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Contracts\Storage\Enums\FetchOrigin;

/**
 * Represents a proxy server configuration entity with methods for managing
 * storage interactions and retrieving data properties. This class extends
 * OrmEntityBase and implements StorageHooksInterface to support database
 * and cache operations.
 */
final class ProxyServer extends OrmEntityBase implements StorageHooksInterface
{
    public string $uniqId;
    public bool $status;
    public ProxyType $type;
    public string $hostname;
    public ?int $port = null;
    public bool $ssl;
    public ?string $sslCaPath = null;
    public string $authType;
    public ?string $authUsername = null;
    public ?string $authPassword = null;
    public int $timeout;
    public int $updatedOn;

    /**
     * @return string
     */
    public function getPrimaryId(): string
    {
        return $this->uniqId;
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        return [
            "uniqId" => $this->uniqId,
            "status" => $this->status,
            "type" => $this->type,
            "hostname" => $this->hostname,
            "port" => $this->port,
            "ssl" => $this->ssl,
            "sslCaPath" => $this->sslCaPath,
            "authType" => $this->authType,
            "authUsername" => $this->authUsername,
            "authPassword" => $this->authPassword,
            "timeout" => $this->timeout,
            "updatedOn" => $this->updatedOn,
        ];
    }

    /**
     * @param FetchOrigin $origin
     * @return string|null
     */
    public function onRetrieve(FetchOrigin $origin): ?string
    {
        if (in_array($origin, [FetchOrigin::Database, FetchOrigin::Cache])) {
            return sprintf('ProxyServer (%s) config retrieved from %s', $this->uniqId, $origin->name);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function onCacheStore(): ?string
    {
        return sprintf('ProxyServer (%s) config STORED in CACHE', $this->uniqId);
    }
}