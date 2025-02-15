<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Buffers\Buffer;
use Charcoal\Http\Router\Controllers\Response\BodyResponse;

/**
 * Class AppAwareEndpoint
 * @package App\Shared\Core\Http
 */
abstract class AbstractHtmlEndpoint extends AppAwareEndpoint
{
    private readonly string $templateDirectory;
    protected bool $exceptionReturnTrace = true;

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
     * @return BodyResponse
     */
    protected function response(): BodyResponse
    {
        /** @var BodyResponse */
        return $this->getResponseObject();
    }

    /**
     * @return callable
     */
    final protected function resolveEntrypoint(): callable
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
        $this->send($this->renderTemplateFile($template, $data));
    }

    /**
     * @param Buffer $body
     * @return void
     */
    protected function send(Buffer $body): void
    {
        $this->response()->body->flush()->append($body);
    }

    /**
     * @param \Throwable $t
     * @return void
     */
    final protected function handleException(\Throwable $t): void
    {
        if ($this->response()->getStatusCode() === 200) {
            $this->response()->setStatusCode(500);
        }

        $exception = [
            "class" => $t::class,
            "message" => $t->getMessage(),
            "code" => $t->getCode(),
            "trace" => $this->exceptionReturnTrace ? explode("\n", $t->getTraceAsString()) : [],
        ];

        if ($t->getPrevious()) {
            $prev = $t->getPrevious();
            $exception["previous"] = [
                "class" => $prev::class,
                "message" => $prev->getMessage(),
                "code" => $prev->getCode(),
                "trace" => $this->exceptionReturnTrace ? explode("\n", $prev->getTraceAsString()) : []
            ];
        }

        $this->send($this->renderTemplateFile("crash", ["exception" => $exception]));
    }

    /**
     * Renders a template file and returns Buffer
     * @param string $template
     * @param array $data
     * @return Buffer
     */
    final protected function renderTemplateFile(string $template, array $data = []): Buffer
    {
        $templateFile = $this->templateDirectory . $template . ".php";
        if (!file_exists($templateFile)) {
            throw new \RuntimeException("Template file not found");
        }

        extract($data, EXTR_SKIP);
        if (!ob_start()) {
            throw new \RuntimeException("Failed to start output buffer for templating");
        }

        try {
            include $templateFile;
            return new Buffer(ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw new \RuntimeException("Error rendering template $template: " . $e->getMessage(), 0, $e);
        }
    }
}