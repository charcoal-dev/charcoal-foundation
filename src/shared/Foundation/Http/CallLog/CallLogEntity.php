<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use Charcoal\App\Kernel\Entity\ChecksumAwareEntityTrait;
use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;
use Charcoal\Buffers\Buffer;
use Charcoal\Buffers\Frames\Bytes20;
use Charcoal\HTTP\Commons\HttpMethod;
use Charcoal\OOP\Vectors\DsvString;

/**
 * Class CallLogEntity
 * @package App\Shared\Foundation\Http\CallLog
 */
class CallLogEntity extends AbstractOrmEntity
{
    public int $id;
    public Bytes20 $checksum;
    public ?string $proxyId;
    public ?DsvString $flags;
    public HttpMethod $method;
    public string $urlServer;
    public string $urlPath;
    public float $startOn;
    public ?float $endOn;
    public ?int $responseCode;
    public ?int $responseLength;
    public ?Buffer $snapshot;

    use ChecksumAwareEntityTrait;

    /**
     * @return int
     */
    public function getPrimaryId(): int
    {
        return $this->id;
    }

    /**
     * @return Bytes20
     */
    public function getChecksum(): Bytes20
    {
        return $this->checksum;
    }

    /**
     * @return array
     */
    protected function collectSerializableData(): array
    {
        return [
            "id" => $this->id,
            "checksum" => $this->checksum,
            "proxyId" => $this->proxyId,
            "flags" => $this->flags,
            "method" => $this->method,
            "urlServer" => $this->urlServer,
            "urlPath" => $this->urlPath,
            "startOn" => $this->startOn,
            "endOn" => $this->endOn,
            "responseCode" => $this->responseCode,
            "responseLength" => $this->responseLength,
            "snapshot" => $this->snapshot,
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
        $data["flags"] = $this->flags->toString();
        $data["method"] = $this->method->value;
        $data["snapshot"] = $this->snapshot?->hash()->sha1();
        return array_values($data);
    }
}