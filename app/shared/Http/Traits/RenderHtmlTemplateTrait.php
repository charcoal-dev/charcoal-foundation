<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared\Http\Traits;

use Charcoal\Buffers\BufferImmutable;
use Charcoal\Filesystem\Enums\Assert;
use Charcoal\Filesystem\Exceptions\FilesystemException;
use Charcoal\Filesystem\Path\PathInfo;

/**
 * Provides functionality to render an HTML template file with injected data.
 * The trait ensures proper output buffering and error handling during the rendering process.
 */
trait RenderHtmlTemplateTrait
{
    /**
     * Renders a template file using the provided data and returns the output as a buffer.
     */
    final protected static function renderTemplateFile(
        PathInfo|string $templateFilepath,
        array           $data = []
    ): BufferImmutable
    {
        if (!$templateFilepath instanceof PathInfo) {
            try {
                $templateFilepath = new PathInfo($templateFilepath);
            } catch (FilesystemException $e) {
                throw new \RuntimeException("Failed to resolve template path: " . $e->getMessage(), 0, $e);
            }
        }

        if (!$templateFilepath->assertQuite(Assert::Exists, Assert::IsFile, Assert::Readable)) {
            throw new \RuntimeException("Template file does not exist or is not readable");
        }

        $templateFilepath = match (true) {
            $templateFilepath instanceof PathInfo => $templateFilepath->absolute,
            default => (string)$templateFilepath
        };

        extract($data, EXTR_SKIP);
        if (!ob_start()) {
            throw new \RuntimeException("Failed to start output buffer for templating");
        }

        try {
            include $templateFilepath;
            return new BufferImmutable(ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Error rendering template: " . $e->getMessage(), 0, $e);
        }
    }
}