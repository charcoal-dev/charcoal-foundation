<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Sapi\Web\Core;

use App\Shared\Sapi\Http\Html\RenderHtmlTemplateTrait;
use Charcoal\Base\Objects\Traits\NotSerializableTrait;
use Charcoal\Contracts\Charsets\Charset;
use Charcoal\Filesystem\Node\DirectoryNode;
use Charcoal\Filesystem\Path\PathInfo;
use Charcoal\Http\Commons\Enums\ContentType;
use Charcoal\Http\Server\Request\Controller\GatewayFacade;

/**
 * Provides functionality to render an HTML template file with injected data.
 */
trait WebTemplatesTrait
{
    use NotSerializableTrait;
    use RenderHtmlTemplateTrait;

    protected readonly DirectoryNode $templatesDirectory;

    /**
     * @throws \Charcoal\Http\Server\Exceptions\Internal\Response\BypassEncodingInterrupt
     */
    final protected function sendTemplate(
        GatewayFacade $gatewayFacade,
        string        $template,
        array         $data = [],
        int           $statusCode = 200,
        bool          $isCacheable = true,
        ContentType   $contentType = ContentType::Html,
        Charset       $charset = Charset::UTF8,
    ): never
    {
        $gatewayFacade->sendResponseBypassEncoding(
            self::renderTemplateFile($this->templateFilePath($template), $data),
            $isCacheable,
            $contentType,
            $statusCode,
            $charset
        );
    }

    /**
     * @param string $file
     * @return PathInfo
     */
    final protected function templateFilePath(string $file): PathInfo
    {
        try {
            if (!isset($this->templatesDirectory)) {
                $this->templatesDirectory = new DirectoryNode(
                    new PathInfo(realpath(charcoal_from_sapi("../templates")))
                );
            }

            if (!str_ends_with($file, ".phtml")) {
                $file .= ".phtml";
            }

            return $this->templatesDirectory->childPathInfo($file);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to resolve template file path", previous: $e);
        }
    }
}