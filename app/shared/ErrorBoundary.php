<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace App\Shared;

use App\Shared\Http\Traits\RenderHtmlTemplateTrait;
use Charcoal\App\Kernel\Internal\Exceptions\AppCrashException;
use Charcoal\App\Kernel\Support\ErrorHelper;

/**
 * A final, readonly class dedicated to handling application crash scenarios by rendering an HTML page.
 * Extends the core ErrorBoundary capabilities and enhances it with HTML template rendering.
 */
final class ErrorBoundary extends \Charcoal\App\Kernel\Support\ErrorBoundary
{
    use RenderHtmlTemplateTrait;

    public static function crashHtmlPage(
        AppCrashException|\Throwable $exIn,
        string                       $crashHtmlTemplate,
    ): void
    {
        $exceptionDto = ErrorHelper::getExceptionDto($exIn);
        header("Content-Type: text/html", response_code: 500);
        header("Cache-Control: no-store, no-cache, must-revalidate");
        print self::renderTemplateFile($crashHtmlTemplate, ["exception" => $exceptionDto])->bytes();
    }
}