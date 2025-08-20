<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Html;

use App\Shared\Core\Http\AbstractAppEndpoint;
use Charcoal\Buffers\Buffer;
use Charcoal\Filesystem\Exceptions\InvalidPathException;
use Charcoal\Filesystem\Path\DirectoryPath;
use Charcoal\Http\Commons\Header\WritableHeaders;
use Charcoal\Http\Router\Response\BodyResponse;

/**
 * Class AbstractHtmlEndpoint
 * @package App\Shared\Core\Http\Html
 */
abstract class AbstractHtmlEndpointAbstract extends AbstractAppEndpoint
{
    private readonly DirectoryPath $templateDirectory;

    use RenderHtmlTemplateTrait;

    abstract protected function entrypoint(): void;

    abstract protected function resolveTemplateDirectory(): DirectoryPath;

    /**
     * @return BodyResponse
     * @api
     */
    final protected function createResponseObject(): BodyResponse
    {
        return new BodyResponse(new WritableHeaders());
    }

    /**
     * @return BodyResponse
     */
    public function response(): BodyResponse
    {
        /** @var BodyResponse */
        return $this->getResponseObject();
    }

    /**
     * @return callable
     */
    final protected function resolveEntryPointMethod(): callable
    {
        $this->getResponseObject()->headers->set("Content-Type", "text/html");

        // Sets template directory
        $this->templateDirectory = $this->resolveTemplateDirectory();

        // Resolve to entrypoint method
        return [$this, "entrypoint"];
    }

    /**
     * @param string $template
     * @param array $data
     * @return void
     * @throws InvalidPathException
     * @throws \Charcoal\Filesystem\Exceptions\FilesystemException
     */
    protected function sendTemplate(string $template, array $data = []): void
    {
        $this->setBody($this->renderTemplateFile(
            $this->templateDirectory->join($template . ".phtml")->isFile(),
            $data
        ));
    }

    /**
     * @param Buffer $body
     * @return void
     */
    protected function setBody(Buffer $body): void
    {
        $this->response()->body->flush()->append($body);
    }

    /**
     * @param \Throwable $t
     * @return void
     * @throws \Throwable
     */
    final protected function handleException(\Throwable $t): void
    {
        // Forward to application ErrorHandler
        throw $t;
    }
}