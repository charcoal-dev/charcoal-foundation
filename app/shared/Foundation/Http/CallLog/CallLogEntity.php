<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Foundation\Http\CallLog;

use Charcoal\App\Kernel\Orm\Entity\OrmEntityBase;
use Charcoal\Base\Support\DsvString;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Commons\Enums\HttpMethod;

/**
 * Class CallLogEntity
 * @package App\Shared\Foundation\Http\CallLog
 */
final class CallLogEntity extends OrmEntityBase
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
        throw new \LogicException(self::class . " does not need to be serialized");
    }
}