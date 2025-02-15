<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\InterfaceLog;

use App\Shared\Foundation\Http\HttpInterface;
use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\HttpMethod;

/**
 * Class InterfaceLogEntity
 * @package App\Shared\Foundation\Http\InterfaceLog
 */
class InterfaceLogEntity extends AbstractOrmEntity
{
    public int $id;
    public HttpInterface $interface;
    public string $ipAddress;
    public HttpMethod $method;
    public string $endpoint;
    public float $startOn;
    public ?float $endOn;
    public ?int $responseCode;
    public ?int $flagSid = null;
    public ?int $flagUid = null;
    public ?Buffer $snapshot;

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
        throw new \LogicException(static::class . " does not need to be serialized");
    }
}