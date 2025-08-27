<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Http;


/**
 * This class extends the RouterPolicy and provides a specific configuration
 * for inbound and outbound headers and payload handling, using the
 * RouterLogger for logging purposes.
 */
final readonly class RouterConfig extends \Charcoal\Http\Router\Config\RouterConfig
{
    public function __construct(
        bool   $parsePayloadKeepBody = false,
        string $parsePayloadUndefinedParam = "json"
    )
    {
        parent::__construct($parsePayloadKeepBody, $parsePayloadUndefinedParam);
    }
}