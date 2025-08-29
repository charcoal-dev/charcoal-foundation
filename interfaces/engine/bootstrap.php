<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

$depth = (int)getenv("CHARCOAL_SAPI_DEPTH");
if (!$depth) {
    throw new \Exception("CHARCOAL_SAPI_DEPTH environment variable is not set.");
}

chdir(__DIR__);
require_once str_repeat("../", $depth) . "dev/bootstrap.php";