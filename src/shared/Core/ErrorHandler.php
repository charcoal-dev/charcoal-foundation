<?php
declare(strict_types=1);

namespace App\Shared\Core;

use App\Shared\CharcoalApp;
use App\Shared\Core\Http\Html\RenderHtmlTemplateTrait;
use Charcoal\App\Kernel\Build\AppBuildEnum;
use Charcoal\App\Kernel\Errors\ErrorLoggerInterface;

/**
 * Class ErrorHandler
 * @package App\Shared\Core
 */
class ErrorHandler extends \Charcoal\App\Kernel\Errors\ErrorHandler
{
    use RenderHtmlTemplateTrait;

    private readonly string $crashHtmlFile;

    /**
     * @param CharcoalApp $app
     * @param AppBuildEnum $build
     * @param ErrorLoggerInterface $logger
     */
    public function __construct(CharcoalApp $app, AppBuildEnum $build, ErrorLoggerInterface $logger)
    {
        parent::__construct($app, $build, $logger);
        $this->crashHtmlFile = $app->directories->storage->pathToChild("./crash.phtml");
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        $data = parent::__serialize();
        $data["crashHtmlFile"] = $this->crashHtmlFile;
        return $data;
    }

    /**
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
        $this->crashHtmlFile = $data["crashHtmlFile"];
    }

    /**
     * @param \Throwable $t
     * @return never
     */
    public function handleThrowable(\Throwable $t): never
    {
        $exception = [
            "class" => get_class($t),
            "message" => $t->getMessage(),
            "code" => $t->getCode(),
            "file" => $this->getOffsetFilepath($t->getFile()),
            "line" => $t->getLine(),
        ];

        if ($this->exceptionHandlerShowTrace) {
            $exception["trace"] = explode("\n", $t->getTraceAsString());
        }

        if (isset($this->crashHtmlFile)) {
            header("Content-Type: text/html", response_code: 500);
            print($this->renderTemplateFile($this->crashHtmlFile, ["exception" => $exception])->raw());
            exit();
        } else {
            header("Content-Type: application/json", response_code: 500);
            exit(json_encode(["FatalError" => $exception]));
        }
    }
}