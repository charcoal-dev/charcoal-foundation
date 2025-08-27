<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\HttpInterfaceProfile;
use App\Shared\Foundation\Http\Config\HttpInterfaceConfig;
use App\Shared\Foundation\Http\HttpInterface;

/**
 * Class ApiInterfaceProfile
 * @package App\Shared\Core\Http\Api
 */
class ApiInterfaceProfile extends HttpInterfaceProfile
{
    public readonly ApiNamespaceInterface $namespace;

    public function __construct(
        CharcoalApp            $app,
        HttpInterface          $enum,
        ApiNamespaceInterface $namespace,
        bool                   $useStaticConfig,
        bool                   $useObjectStoreConfig,
        string                 $configClass = HttpInterfaceConfig::class,
    )
    {
        parent::__construct($app, $enum, $useStaticConfig, $useObjectStoreConfig, $configClass);
        $this->namespace = $namespace;
    }
}