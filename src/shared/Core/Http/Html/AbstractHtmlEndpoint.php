<?php
declare(strict_types=1);

namespace App\Shared\Core\Http\Html;

use App\Shared\Core\Http\AppAwareEndpoint;
use Charcoal\Buffers\Buffer;
use Charcoal\Http\Router\Controllers\Response\BodyResponse;

/**
 * Class AbstractHtmlEndpoint
 * @package App\Shared\Core\Http\Html
 */
abstract class AbstractHtmlEndpoint extends AppAwareEndpoint
{
    private readonly string $templateDirectory;

    use RenderHtmlTemplateTrait;

    abstract protected function entrypoint(): void;

    abstract protected function resolveTemplateDirectory(): string;

    /**
     * @return BodyResponse
     */
    final protected function initEmptyResponse(): BodyResponse
    {
        return new BodyResponse();
    }

    /**
     * @return void
     */
    protected function prepareResponseCallback(): void
    {
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
     */
    protected function sendTemplate(string $template, array $data = []): void
    {
        $this->setBody($this->renderTemplateFile($this->templateDirectory . $template . ".phtml", $data));
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