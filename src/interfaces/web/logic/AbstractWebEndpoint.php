<?php
declare(strict_types=1);

namespace App\Interfaces\Web;

use App\Shared\Core\Http\AbstractHtmlEndpoint;

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

    /**
     * @return void
     */
    protected function beforeEntrypointCallback(): void
    {
    }
}