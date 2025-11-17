<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace Dev;

use App\Shared\CharcoalApp;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Database\Orm\Migrations;
use Charcoal\Filesystem\Path\DirectoryPath;

putenv("CHARCOAL_SAPI_ROOT=" . __DIR__);
putenv("CHARCOAL_SAPI_DEPTH=2");
require "../bootstrap.php";
charcoal_autoloader();

$timestamp = MonotonicTimestamp::now();
$charcoal = CharcoalApp::Load(AppEnv::Dev,
    (new DirectoryPath(charcoal_from_root()))->node(),
    ["var", "shared"]);

$charcoal->bootstrap($timestamp);
$createTable = Migrations::createTable(
    $charcoal->coreData->objectStore->table,
    true
);

print(implode("\n", $createTable) . "\n");