<?php
declare(strict_types=1);

namespace App\Shared\Foundation\Http\ProxyServers;

use Charcoal\HTTP\Client\Response;
use Charcoal\OOP\Traits\EnumOptionsTrait;

/**
 * Class ProxyType
 * @package App\Shared\Foundation\Http\ProxyServers
 */
enum ProxyType: string
{
    case SQUID = "squid";
    case SQUID5 = "squid5";
    case SQUID6 = "squid6";

    use EnumOptionsTrait;

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