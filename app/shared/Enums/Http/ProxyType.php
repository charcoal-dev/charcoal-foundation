<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Enums\Http;

use Charcoal\Base\Traits\EnumMappingTrait;
use Charcoal\Http\Client\Response;

/**
 * This enum includes different proxy types (e.g., SQUID, SQUID5, SQUID6),
 * and provides functionality for connection verification and server validation.
 */
enum ProxyType: string
{
    case SQUID = "squid";
    case SQUID5 = "squid5";
    case SQUID6 = "squid6";

    use EnumMappingTrait;

    /**
     * @param Response $response
     * @return void
     */
    public function verifyConnection(Response $response): void
    {
        match ($this) {
            self::SQUID,
            self::SQUID5,
            self::SQUID6 => $this->validateSquidServer($response),
        };
    }

    /**
     * @param Response $response
     * @return void
     */
    private function validateSquidServer(Response $response): void
    {
        $serverType = $response->headers->get("server");
        if (!$serverType) {
            throw new \UnexpectedValueException('Expected HTTP header "Server" from SQUID');
        }

        $serverRegExp = match ($this->value) {
            "squid" => "/^squid/",
            "squid5" => "/^squid\/5\./i",
            "squid6" => "/^squid\/6\./i",
        };

        if (!preg_match($serverRegExp, $serverType)) {
            throw new \RuntimeException("Failed to validate server as " . $this->name);
        }
    }
}