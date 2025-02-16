<?php
declare(strict_types=1);

namespace App\Shared\Core;

use App\Shared\Core\Html\RenderHtmlTemplateTrait;

/**
 * Class ErrorHandler
 * @package App\Shared\Core
 */
class ErrorHandler extends \Charcoal\App\Kernel\Errors\ErrorHandler
{
    use RenderHtmlTemplateTrait;

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

        header("Content-Type: text/html", response_code: 500);
        print($this->renderTemplateFile(__DIR__ . "/Html/crash.phtml", ["exception" => $exception])->raw());
        exit();
    }
}