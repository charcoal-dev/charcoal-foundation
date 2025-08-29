<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

if (defined("CHARCOAL_ROOT") ||
    defined("CHARCOAL_SAPI_ENTRYPOINT") ||
    defined("CHARCOAL_SAPI_ROOT") ||
    defined("CHARCOAL_SAPI_DEPTH") ||
    defined("CHARCOAL_IN_DOCKER")
) {
    throw new RuntimeException("Charcoal app bootstrap already loaded");
}

if (!getenv("CHARCOAL_SAPI_ROOT") || !getenv("CHARCOAL_SAPI_DEPTH")) {
    throw new RuntimeException("Required CHARCOAL_SAPI_ROOT or CHARCOAL_SAPI_DEPTH not set");
}

// Get the declared directory for SAPI service
$sapiRoot = getenv("CHARCOAL_SAPI_ROOT");
if (!$sapiRoot || !is_dir($sapiRoot)) {
    throw new RuntimeException("CHARCOAL_SAPI_ROOT environment variable is not set or not a directory");
}

define("CHARCOAL_SAPI_ROOT", $sapiRoot);

// Get the declared depth for SAPI service
$sapiDepth = (int)getenv("CHARCOAL_SAPI_DEPTH");
if ($sapiDepth < 1 || $sapiDepth > 6) {
    throw new RuntimeException("CHARCOAL_SAPI_DEPTH environment variable is not set or invalid");
}

define("CHARCOAL_SAPI_DEPTH", $sapiRoot);

// Get the root directory for Charcoal App
define("CHARCOAL_ROOT", realpath(dirname(CHARCOAL_SAPI_ROOT, $sapiDepth)));

// Entrypoint
define("CHARCOAL_SAPI_ENTRYPOINT", $_SERVER["SCRIPT_FILENAME"] ??
    debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]["file"] ??
    __FILE__);

// Is in Docker?
define("CHARCOAL_IN_DOCKER", defined("CHARCOAL_DOCKER") && @file_exists("/.dockerenv"));

// Helpers
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
