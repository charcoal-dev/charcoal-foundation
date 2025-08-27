<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Interfaces\Web;

use App\Shared\Core\Config\Persisted\HttpInterfaceConfig;
use App\Shared\Core\Http\Html\AbstractHtmlEndpointAbstract;
use App\Shared\Core\Http\HttpInterfaceProfile;
use App\Shared\Enums\Http\HttpInterface;
use Charcoal\Filesystem\Path\DirectoryPath;

/**
 * This class defines the core structure and behavior shared by all web endpoints.
 * It provides functionality to resolve the template directory and declare the HTTP interface.
 */
abstract class AbstractWebEndpoint extends AbstractHtmlEndpointAbstract
{
    protected function appEndpointCallback(): void
    {
    }

    /**
     * @return DirectoryPath
     */
    final protected function resolveTemplateDirectory(): DirectoryPath
    {
        return $this->app->paths->templates;
    }

    /**
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityNotFoundException
     * @throws \Charcoal\App\Kernel\Orm\Exceptions\EntityRepositoryException
     * @throws \Charcoal\Cipher\Exceptions\CipherException
     */
    protected function declareHttpInterface(): HttpInterfaceProfile
    {
        return new HttpInterfaceProfile($this->app, HttpInterface::Web, true, false, HttpInterfaceConfig::class);
    }
}