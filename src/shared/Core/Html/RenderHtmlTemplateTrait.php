<?php
declare(strict_types=1);

namespace App\Shared\Core\Html;

use Charcoal\Buffers\Buffer;

/**
 * Trait RenderHtmlTemplateTrait
 * @package App\Shared\Core\Html
 */
trait RenderHtmlTemplateTrait
{
    /**
     * @param string $templateFilepath
     * @param array $data
     * @return Buffer
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