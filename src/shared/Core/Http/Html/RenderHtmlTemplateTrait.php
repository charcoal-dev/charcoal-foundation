<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Html;

use Charcoal\Buffers\Buffer;

/**
 * Provides functionality to render an HTML template file with injected data.
 * The trait ensures proper output buffering and error handling during the rendering process.
 */
trait RenderHtmlTemplateTrait
{
    /**
     * Renders a template file using the provided data and returns the output as a buffer.
     */
    final protected function renderTemplateFile(string $templateFilepath, array $data = []): Buffer
    {
        if (!file_exists($templateFilepath)) {
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