<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Api;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\HttpInterfaceBinding;
use App\Shared\Foundation\Http\Config\HttpInterfaceConfig;
use App\Shared\Foundation\Http\HttpInterface;

/**
 * Class ApiInterfaceBinding
 * @package App\Shared\Core\Http\Api
 */
class ApiInterfaceBinding extends HttpInterfaceBinding
{
    public readonly ?ApiNamespaceInterface $namespace;

    public function __construct(
        CharcoalApp          $app,
        public HttpInterface $enum,
        bool                 $useStaticConfig,
        bool                 $useObjectStoreConfig,
        string               $configClass = HttpInterfaceConfig::class,
    )
    {
        parent::__construct($app, $enum, $useStaticConfig, $useObjectStoreConfig, $configClass);
    }
}