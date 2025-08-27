<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Interfaces\Web;

use Charcoal\Base\Support\Helpers\ObjectHelper;
use Charcoal\Filesystem\Exceptions\FilesystemException;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Composer\InstalledVersions;

/**
 * Handles fallback rendering for the application.
 */
class FallbackEndpoint extends AbstractWebEndpoint
{
    /**
     * @throws InvalidPathException
     * @throws FilesystemException
     */
    protected function entrypoint(): void
    {
        $this->sendTemplate("fallback", [
            "appClassname" => ObjectHelper::baseClassName($this->app::class),
            "appKernelBuild" => InstalledVersions::getVersion("charcoal-dev/app-kernel"),
            "modulesLoaded" => array_keys($this->app->context->domain)
        ]);
    }
}