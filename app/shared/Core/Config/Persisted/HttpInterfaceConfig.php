<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Persisted;

use App\Shared\Contracts\Config\PersistedConfigProvidesSnapshot;
use App\Shared\Enums\Http\HttpLogLevel;
use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;

/**
 * Represents the configuration of an HTTP interface, providing options to control logging,
 * tracing, and caching behavior.
 */
class HttpInterfaceConfig extends AbstractResolvedConfig implements
    PersistedConfigProvidesSnapshot
{
    public bool $status;
    public HttpLogLevel $logData;
    public bool $logHttpMethodOptions;
    public ?string $traceHeader;
    public ?string $cachedResponseHeader;

    /**
     * @return ConfigSnapshotInterface
     */
    public function snapshot(): ConfigSnapshotInterface
    {
        return new \App\Shared\Core\Config\Snapshot\HttpInterfaceConfig(
            $this->status,
            $this->logData,
            $this->logHttpMethodOptions,
            $this->traceHeader,
            $this->cachedResponseHeader,
        );
    }
}