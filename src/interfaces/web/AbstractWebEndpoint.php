<?php
declare(strict_types=1);

namespace App\Interfaces\Web;

use App\Shared\Core\Http\Html\AbstractHtmlEndpoint;
use App\Shared\Core\Http\HttpInterfaceBinding;
use App\Shared\Foundation\Http\Config\HttpInterfaceConfig;
use App\Shared\Foundation\Http\HttpInterface;

/**
 * Class AbstractWebEndpoint
 * @package App\Interfaces\Web
 */
abstract class AbstractWebEndpoint extends AbstractHtmlEndpoint
{
    /**
     * @return string
     */
    final protected function resolveTemplateDirectory(): string
    {
        return __DIR__ . "/../templates/";
    }


    protected function appAwareCallback(): void
    {
    }

    protected function declareHttpInterface(): ?HttpInterfaceBinding
    {
        return new HttpInterfaceBinding($this->app, HttpInterface::WEB, true, false, HttpInterfaceConfig::class);
    }
}