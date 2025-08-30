<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace Dev;

use App\Shared\CharcoalApp;
use App\Shared\Enums\Databases;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Database\Orm\Migrations;
use Charcoal\Filesystem\Path\DirectoryPath;

putenv("CHARCOAL_SAPI_ROOT=" . __DIR__);
putenv("CHARCOAL_SAPI_DEPTH=2");
require "../bootstrap.php";
charcoal_autoloader();

$charcoal = CharcoalApp::Load(AppEnv::Dev,
    (new DirectoryPath(charcoal_from_root()))->node(),
    ["var", "shared"]);

$createTable = Migrations::createTable(
    $charcoal->database->getDb(Databases::Primary),
    $charcoal->http->callLog->table,
    true
);

print(implode("\n", $createTable) . "\n");