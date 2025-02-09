<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\ProxyServers;

use Charcoal\App\Kernel\Contracts\StorageHooks\StorageHooksInterface;
use Charcoal\App\Kernel\Entity\EntitySource;
use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;

/**
 * Class HttpProxy
 * @package App\Shared\Foundation\Http\ProxyServers
 */
class HttpProxy extends AbstractOrmEntity implements StorageHooksInterface
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
     * @param EntitySource $source
     * @return string|null
     */
    public function onRetrieve(EntitySource $source): ?string
    {
        if (in_array($source, [EntitySource::DATABASE, EntitySource::CACHE])) {
            return sprintf('HttpProxy (%s) config retrieved from %s', $this->uniqId, $source->name);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function onCacheStore(): ?string
    {
        return sprintf('HttpProxy (%s) config STORED in CACHE', $this->uniqId);
    }
}