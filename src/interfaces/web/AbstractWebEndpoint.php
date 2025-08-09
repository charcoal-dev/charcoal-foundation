<?php
declare(strict_types=1);

namespace App\Interfaces\Web;

use App\Shared\Core\Http\Html\AbstractHtmlEndpoint;
use App\Shared\Core\Http\HttpInterfaceProfile;
use App\Shared\Foundation\Http\Config\HttpInterfaceConfig;
use App\Shared\Foundation\Http\HttpInterface;

/**
 * Class AbstractWebEndpoint
 * @package App\Interfaces\Web
 */
abstract class AbstractWebEndpoint extends AbstractHtmlEndpoint
{
    final protected function resolveTemplateDirectory(): string
    {
        return $this->app->directories->root->pathToChild("templates/", false);
    }

    protected function appAwareCallback(): void
    {
    }

    protected function declareHttpInterface(): ?HttpInterfaceProfile
    {
        return new HttpInterfaceProfile($this->app, HttpInterface::WEB, true, false, HttpInterfaceConfig::class);
    }
}