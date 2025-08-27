<?php
declare(strict_types=1);

namespace App\Shared\Core\Config\Snapshot;

use App\Shared\Enums\Http\HttpLogLevel;
use Charcoal\App\Kernel\Internal\Config\ConfigSnapshotInterface;
use Charcoal\Http\Commons\Enums\HeaderKeyPolicy;
use Charcoal\Http\Commons\Support\HttpHelper;

/**
 * Class HttpInterfaceConfig
 * @package App\Shared\Core\Config\Snapshot\Http
 */
final readonly class HttpInterfaceConfig implements ConfigSnapshotInterface
{
    public function __construct(
        public bool         $status,
        public HttpLogLevel $logLevel,
        public bool         $logHttpMethodOptions,
        public ?string      $traceHeader,
        public ?string      $cachedResponseHeader,
    )
    {
        if (is_string($this->traceHeader)) {
            if (!HttpHelper::isValidHeaderName($this->traceHeader, HeaderKeyPolicy::STRICT)) {
                throw new \UnexpectedValueException("Invalid trace header name");
            }
        }

        if (is_string($this->cachedResponseHeader)) {
            if (!HttpHelper::isValidHeaderName($this->cachedResponseHeader, HeaderKeyPolicy::STRICT)) {
                throw new \UnexpectedValueException("Invalid trace header name");
            }
        }
    }
}