<?php
declare(strict_types=1);

namespace App\Shared\Core;

use App\Shared\Core\Http\Html\RenderHtmlTemplateTrait;
use Charcoal\App\Kernel\Diagnostics\Diagnostics;
use Charcoal\App\Kernel\Diagnostics\Events\BuildStageEvents;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\App\Kernel\Enums\DiagnosticsEvent;
use Charcoal\App\Kernel\Errors\ErrorLoggers;

/**
 * Manages error handling and the termination process in the application.
 * Extends the functionality of Charcoal's ErrorManager and provides
 * additional support for rendering error templates and serializing state.
 */
final class ErrorManager extends \Charcoal\App\Kernel\Errors\ErrorManager
{
    use RenderHtmlTemplateTrait;

    private readonly string $crashHtmlTemplate;

    /**
     * Error Manager Constructor
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function __construct(AppEnv $env, PathRegistry $paths, ?ErrorLoggers $loggers = null)
    {
        parent::__construct($env, $paths, $loggers);
        Diagnostics::app()->subscribe(DiagnosticsEvent::BuildStage, function (BuildStageEvents $event) use ($paths) {
            if ($event === BuildStageEvents::PathRegistryOn) {
                $this->crashHtmlTemplate = $paths->storage->join("./crash.phtml")->path;
            }
        });
    }

    /**
     * @return array
     */
    public function collectSerializableData(): array
    {
        $data = parent::__serialize();
        $data["crashHtmlTemplate"] = $this->crashHtmlTemplate;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->crashHtmlTemplate = $data["crashHtmlTemplate"];
    }

    /**
     * Handles termination of the application by rendering output in the appropriate format and exiting.
     * @noinspection PhpUnhandledExceptionInspection
     */
    protected function onTerminate(array $exceptionDto): never
    {
        $isCli = php_sapi_name() === "cli";
        if (!$isCli && isset($this->crashHtmlFile) && file_exists($this->crashHtmlFile)) {
            header("Content-Type: text/html", response_code: 500);
            header("Cache-Control: no-store, no-cache, must-revalidate");
            print($this->renderTemplateFile($this->crashHtmlFile, ["exception" => $exceptionDto])->raw());
            exit();
        }

        if (!$isCli) {
            header("Content-Type: application/json", response_code: 500);
            header("Cache-Control: no-store, no-cache, must-revalidate");
        }

        exit(json_encode(["FatalError" => $exceptionDto]));
    }
}