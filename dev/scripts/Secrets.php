<?php
/**
 * Part of the "charcoal-dev/charcoal-foundation" package.
 * @link https://github.com/charcoal-dev/charcoal-foundation
 */

declare(strict_types=1);

namespace Dev;

use App\Shared\CharcoalApp;
use App\Shared\Enums\SecretKeys;
use App\Shared\Enums\SecretsStores;
use Charcoal\App\Kernel\Clock\MonotonicTimestamp;
use Charcoal\App\Kernel\Enums\AppEnv;
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

// Database Password File
$localStore = $charcoal->security->secrets->getStore(SecretsStores::Local);
$passwordFile = $localStore->load("defaultdb_password", 1, $localStore->namespace("passwords/db"), true);
$passwordFile->useSecretEntropy(function (string $bytes) use (&$dbPassword) {
    $dbPassword = $bytes;
});

print "DB Password File: " . strlen($dbPassword) . " Bytes\n";
print "DB Password: " . trim($dbPassword) . " (" . strlen(trim($dbPassword)) . " Bytes)\n";

// Primary Key
$primaryKey = $charcoal->security->secrets->resolveSecretEnum(SecretKeys::Primary);
$primaryKey->useSecretEntropy(function (string $bytes) use (&$entropy) {
    $entropy = $bytes;
});

print "[Primary] Entropy (Raw): " . $entropy . "\n";
print "[Primary] Entropy (Hex): " . bin2hex($entropy) . "\n";

// Remixed Secret Key
$coreDataSecret = $charcoal->security->secrets->resolveSecretEnum(SecretKeys::CoreDataModule);
$coreDataSecret->useSecretEntropy(function (string $bytes) use (&$coreDataKey) {
    $coreDataKey = $bytes;
});

print "[CoreData] Entropy (Raw): " . $coreDataKey . "\n";
print "[CoreData] Entropy (Hex): " . bin2hex($coreDataKey) . "\n";


