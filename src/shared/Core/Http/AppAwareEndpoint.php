<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use App\Shared\CharcoalApp;
use App\Shared\Utility\NetworkValidator;
use Charcoal\App\Kernel\Interfaces\Http\AbstractEndpoint;

/**
 * Class AppAwareEndpoint
 * @package App\Shared\Core\Http
 * @property CharcoalApp $app
 */
abstract class AppAwareEndpoint extends AbstractEndpoint
{
    public readonly string $userIpAddress;

    /**
     * @param array $args
     * @return void
     */
    final protected function onConstruct(array $args): void
    {
        parent::onConstruct($args);
        $this->userIpAddress = $this->userClient->cfConnectingIP ??
            $this->userClient->xForwardedFor ??
            $this->userClient->ipAddress;

        if (!NetworkValidator::isValidIpAddress($this->userIpAddress, true, true)) {
            throw new \UnexpectedValueException("Invalid remote IP address");
        }
    }
}