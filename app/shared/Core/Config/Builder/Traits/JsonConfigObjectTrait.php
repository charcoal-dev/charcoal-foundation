<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Config\Builder\Traits;

use Charcoal\Filesystem\Node\FileNode;
use Charcoal\Filesystem\Path\FilePath;

/**
 * Provides functionality to read and decode a JSON configuration file.
 * @api
 */
trait JsonConfigObjectTrait
{
    /**
     * @param string $filepath
     * @return array
     * @throws \Charcoal\Filesystem\Exceptions\InvalidPathException
     * @throws \Charcoal\Filesystem\Exceptions\NodeOpException
     * @throws \Charcoal\Filesystem\Exceptions\PathNotFoundException
     * @throws \Charcoal\Filesystem\Exceptions\PathTypeException
     * @throws \Charcoal\Filesystem\Exceptions\PermissionException
     */
    protected function readJsonConfigObject(string $filepath): array
    {
        return json_decode((new FileNode(new FilePath($filepath)))->read());
    }
}