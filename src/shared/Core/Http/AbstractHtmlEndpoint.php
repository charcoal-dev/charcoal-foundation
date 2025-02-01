<?php
declare(strict_types=1);

namespace App\Shared\Core\Http;

use Charcoal\Buffers\Buffer;

/**
 * Class AppAwareEndpoint
 * @package App\Shared\Core\Http
 */
abstract class AbstractHtmlEndpoint extends AppAwareEndpoint
{
    private readonly string $templateDirectory;
    protected bool $exceptionReturnTrace = false;

    abstract protected function entrypoint(): void;

    abstract protected function resolveTemplateDirectory(): string;

    /**
     * @return callable
     */
    final protected function resolveEntrypoint(): callable
    {
        $this->response->headers->set("Content-Type", "text/html");

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
        $this->response->body->flush()->append($body);
    }

    /**
     * @param \Throwable $t
     * @return void
     */
    final protected function handleException(\Throwable $t): void
    {
        if ($this->response->getHttpStatusCode() === 200) {
            $this->response->setHttpCode(500);
        }

        try {
            $this->send($this->renderTemplateFile("crash", ["exception" => $t]));
            return;
        } catch (\Exception $e) {
            $this->response->headers->set("Content-Type", "application/json");

            $errorData = [
                "class" => $e::class,
                "message" => $e->getMessage(),
                "code" => $e->getCode(),
                "trace" => $this->exceptionReturnTrace ? explode("\n", $t->getTraceAsString()) : [],
                "previous" => [
                    "class" => $t::class,
                    "message" => $t->getMessage(),
                    "code" => $t->getCode(),
                    "trace" => $this->exceptionReturnTrace ? explode("\n", $t->getTraceAsString()) : []
                ]
            ];

            $this->send(new Buffer(json_encode($errorData, JSON_PRETTY_PRINT)));
        }
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