<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Auth\Sessions;

use App\Shared\Contracts\Accounts\AccountRealm;
use Charcoal\App\Kernel\Contracts\StorageHooks\StorageHooksInterface;
use Charcoal\App\Kernel\Entity\ChecksumAwareEntityInterface;
use Charcoal\App\Kernel\Entity\ChecksumAwareEntityTrait;
use Charcoal\App\Kernel\Entity\EntitySource;
use Charcoal\App\Kernel\Orm\Entity\CacheableEntityInterface;
use Charcoal\App\Kernel\Orm\Entity\CacheableEntityTrait;
use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Frames\Bytes20;
use Charcoal\Buffers\Frames\Bytes32;

/**
 * Class SessionEntity
 * @package App\Shared\Foundation\Auth\Sessions
 */
class SessionEntity extends AbstractOrmEntity implements
    ChecksumAwareEntityInterface,
    CacheableEntityInterface,
    StorageHooksInterface
{
    public int $id;
    public Bytes20 $checksum;
    public AccountRealm $realm;
    public SessionType $type;
    public bool $archived;
    public Bytes32 $token;
    public Bytes32 $deviceFp;
    public Buffer $hmacSecret;
    public string $ipAddress;
    public string $userAgent;
    public int $uid;
    public int $issuedOn;
    public int $lastUsedOn;

    use ChecksumAwareEntityTrait;
    use CacheableEntityTrait;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        return [
            "id" => $this->id,
            "checksum" => $this->checksum,
            "realm" => $this->realm,
            "type" => $this->type,
            "archived" => $this->archived,
            "token" => $this->token,
            "deviceFp" => $this->deviceFp,
            "hmacSecret" => $this->hmacSecret,
            "ipAddress" => $this->ipAddress,
            "userAgent" => $this->userAgent,
            "uid" => $this->uid,
            "issuedOn" => $this->issuedOn,
            "lastUsedOn" => $this->lastUsedOn,
            "entityChecksumValidated" => $this->entityChecksumValidated,
        ];
    }

    /**
     * @return array
     */
    public function collectChecksumData(): array
    {
        $data = $this->collectSerializableData();
        unset($data["checksum"], $data["entityChecksumValidated"]);
        return $data;
    }

    /**
     * @return Bytes20
     */
    public function getChecksum(): Bytes20
    {
        return $this->checksum;
    }

    /**
     * @param EntitySource $source
     * @return string|null
     */
    public function onRetrieve(EntitySource $source): ?string
    {
        if (in_array($source, [EntitySource::DATABASE, EntitySource::CACHE])) {
            return sprintf('%s session (#%d) retrieved from %s', $this->realm->name, $this->id, $source->name);
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function onCacheStore(): ?string
    {
        return sprintf('%s session (#%d) stored in CACHE', $this->realm->name, $this->id);
    }
}