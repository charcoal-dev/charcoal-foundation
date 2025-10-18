<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace Dev;

use App\Shared\CharcoalApp;
use App\Shared\Enums\Databases;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
use Charcoal\Console\Ansi\AnsiDecorator;
use Charcoal\Database\Events\Connection\ConnectionError;
use Charcoal\Database\Events\Connection\ConnectionSuccess;
use Charcoal\Database\Events\Connection\ConnectionWaiting;
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

# Setup Events
$charcoal->events->database(Databases::Primary)->onLazyConnect(function (ConnectionWaiting $connection) {
    print AnsiDecorator::parse(
        sprintf("{magenta2}Lazy Database Connection:{/} {yellow}[%s]{/} {cyan}%s{/}@{blue}%s{/}\n",
            $connection->credentials->driver->name, $connection->credentials->dbName, $connection->credentials->host));
});

$charcoal->events->database(Databases::Primary)->onConnect(function (ConnectionSuccess $connection) {
    print AnsiDecorator::parse(
        sprintf("{green}Database Connection Success{/} ... {yellow}[%s]:{/} {cyan}%s{/}@{blue}%s{/}\n",
            $connection->credentials->driver->name, $connection->credentials->dbName, $connection->credentials->host));
});

$charcoal->events->database(Databases::Primary)->onConnectionError(function (ConnectionError $connection) {
    print AnsiDecorator::parse(
        sprintf("{red}Database Connection Failed{/} ... %s\n",
            \Charcoal\App\Kernel\Support\ErrorHelper::exception2String($connection->exception)));
});

$db = $charcoal->database->getDb(Databases::Primary);
$db->pdoAdapter();