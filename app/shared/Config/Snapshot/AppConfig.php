<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Config\Snapshot;

use App\Shared\Http\Client\HttpClientConfig;
use Charcoal\App\Kernel\Config\Snapshot\CacheManagerConfig;
use Charcoal\App\Kernel\Config\Snapshot\DatabaseManagerConfig;
use Charcoal\App\Kernel\Config\Snapshot\SapiConfigBundle;
use Charcoal\App\Kernel\Config\Snapshot\SecurityConfig;
use Charcoal\App\Kernel\Contracts\Enums\TimezoneEnumInterface;
use Charcoal\App\Kernel\Enums\AppEnv;

/**
 * This class provides initialization of the application's environment, timezone settings,
 * and optionally the cache and database manager configurations.
 */
final readonly class AppConfig extends \Charcoal\App\Kernel\Config\Snapshot\AppConfig
{
    public function __construct(
        AppEnv                  $env,
        TimezoneEnumInterface   $timezone,
        ?CacheManagerConfig     $cache,
        ?DatabaseManagerConfig  $database,
        SecurityConfig          $security,
        SapiConfigBundle        $sapi,
        public ?MailerConfig    $mailer,
        public HttpClientConfig $httpClient,
    )
    {
        parent::__construct($env, $timezone, $cache, $database, $security, $sapi);
    }
}