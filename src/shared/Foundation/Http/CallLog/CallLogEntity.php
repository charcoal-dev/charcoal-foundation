<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use Charcoal\App\Kernel\Orm\Repository\AbstractOrmEntity;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\HttpMethod;
use Charcoal\OOP\Vectors\DsvString;

/**
 * Class CallLogEntity
 * @package App\Shared\Foundation\Http\CallLog
 */
class CallLogEntity extends AbstractOrmEntity
{
    public int $id;
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