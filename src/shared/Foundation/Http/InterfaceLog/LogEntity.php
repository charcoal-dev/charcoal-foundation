<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\InterfaceLog;

use App\Shared\Enums\Http\HttpInterface;
use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Enums\HttpMethod;

/**
 * Represents a log entity that records details about an HTTP request/response interaction.
 * Extends the base ORM entity and provides functionality for accessing key properties and snapshot data.
 */
final class LogEntity extends OrmEntityBase
{
    public int $id;
    public HttpInterface $interface;
    public string $ipAddress;
    public HttpMethod $method;
    public string $endpoint;
    public float $startOn;
    public ?float $endOn;
    public int $alerts;
    public ?int $responseCode;
    public ?int $flagSid = null;
    public ?int $flagUid = null;
    public ?int $flagTid = null;
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
        throw new \LogicException(self::class . " does not need to be serialized");
    }

    /**
     * @return RequestSnapshot|null
     * @api
     */
    public function getSnapshotObject(): ?RequestSnapshot
    {
        if (!$this->snapshot || $this->snapshot->len() < 1) {
            return null;
        }

        $snapshot = unserialize($this->snapshot->raw(), ["allowed_classes" => [RequestSnapshot::class]]);
        if (!$snapshot instanceof RequestSnapshot) {
            throw new \RuntimeException(
                sprintf('%s encountered value of type "%s"',
                    __METHOD__,
                    is_object($snapshot) ? get_class($snapshot) : gettype($snapshot)
                )
            );
        }

        return $snapshot;
    }
}