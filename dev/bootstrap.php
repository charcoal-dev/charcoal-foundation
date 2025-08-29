<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

if (defined("CHARCOAL_ROOT") ||
    defined("CHARCOAL_SAPI_ENTRYPOINT") ||
    defined("CHARCOAL_SAPI_ROOT") ||
    defined("CHARCOAL_IN_DOCKER") ||
    defined("CHARCOAL_SAPI_DEPTH")
) {
    throw new RuntimeException("Charcoal app bootstrap already loaded");
}

define("CHARCOAL_SAPI_ENTRYPOINT", $_SERVER["SCRIPT_FILENAME"] ??
    debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["file"] ??
    __FILE__);

define("CHARCOAL_IN_DOCKER", @file_exists("/.dockerenv"));
define("CHARCOAL_SAPI_DEPTH", match (basename(dirname(CHARCOAL_SAPI_ENTRYPOINT))) {
    "engine" => CHARCOAL_IN_DOCKER ? 1 : 2,
    "web" => CHARCOAL_IN_DOCKER ? 2 : 3,
    default => throw new RuntimeException("Unknown SAPI"),
});

define("CHARCOAL_SAPI_ROOT", getenv("CHARCOAL_SAPI_ROOT") ?: dirname(CHARCOAL_SAPI_ENTRYPOINT));
define("CHARCOAL_ROOT", realpath(dirname(CHARCOAL_SAPI_ROOT, CHARCOAL_SAPI_DEPTH)));

if (!function_exists("charcoal_from_root")) {
    function charcoal_from_root(string $path = ""): string
    {
        return rtrim(CHARCOAL_ROOT, "/") . "/" . ltrim($path, "/");
    }
}

if (!function_exists("charcoal_from_sapi")) {
    function charcoal_from_sapi(string $path = ""): string
    {
        return rtrim(CHARCOAL_SAPI_ROOT, "/") . "/" . ltrim($path, "/");
    }
}

if (!function_exists("charcoal_autoloader")) {
    function charcoal_autoloader(): void
    {
        $autoload = charcoal_from_root("dev/composer/vendor/autoload.php");
        if (!is_file($autoload)) {
            fwrite(STDERR, "[charcoal] Missing Composer autoload at: $autoload\n");
            exit(1);
        }

        require_once $autoload;
    }
}
