<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Core\Http\Html;

use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Enums\PathType;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\Exceptions\PathTypeException;
use Charcoal\Filesystem\Path\FilePath;
use Charcoal\Filesystem\Path\SafePath;

/**
 * Provides functionality to render an HTML template file with injected data.
 * The trait ensures proper output buffering and error handling during the rendering process.
 */
trait RenderHtmlTemplateTrait
{
    /**
     * Renders a template file using the provided data and returns the output as a buffer.
     * @throws InvalidPathException
     * @throws PathTypeException
     */
    final protected function renderTemplateFile(FilePath|SafePath|string $templateFilepath, array $data = []): Buffer
    {
        if (!$templateFilepath instanceof FilePath) {
            $templateFilepath = new FilePath($templateFilepath);
        }

        if ($templateFilepath->type !== PathType::File) {
            throw new \RuntimeException("Template file not found");
        }

        extract($data, EXTR_SKIP);
        if (!ob_start()) {
            throw new \RuntimeException("Failed to start output buffer for templating");
        }

        try {
            include $templateFilepath;
            return new Buffer(ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Error rendering template: " . $e->getMessage(), 0, $e);
        }
    }
}