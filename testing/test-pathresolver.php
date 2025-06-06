<?php
/**
 * Test script for PathResolver class
 * Tests the shared hosting compatibility layer
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the PathResolver
require_once __DIR__ . '/../src/SharedHosting/PathResolver.php';

use FileRise\SharedHosting\PathResolver;

echo "=== PathResolver Test ===\n\n";

// 1. Test project root detection
echo "1. Project Root Detection:\n";
$projectRoot = PathResolver::getProjectRoot();
echo "   Detected root: $projectRoot\n";
echo "   Is valid: " . (is_dir($projectRoot) ? 'YES' : 'NO') . "\n\n";

// 2. Test shared hosting detection
echo "2. Shared Hosting Detection:\n";
$isShared = PathResolver::isSharedHosting();
echo "   Is shared hosting: " . ($isShared ? 'YES' : 'NO') . "\n";
echo "   open_basedir: " . (ini_get('open_basedir') ?: 'not set') . "\n\n";

// 3. Test data paths
echo "3. Data Path Resolution:\n";
$types = ['uploads', 'users', 'metadata', 'trash'];
foreach ($types as $type) {
    $path = PathResolver::getDataPath($type);
    echo "   $type: $path\n";
    echo "      Exists: " . (is_dir($path) ? 'YES' : 'NO') . "\n";
    echo "      Writable: " . (is_writable($path) ? 'YES' : 'NO') . "\n";
}
echo "\n";

// 4. Test include paths
echo "4. Include Path Resolution:\n";
$includes = [
    ['model', 'User.php'],
    ['controller', 'AuthController.php'],
    ['vendor', 'autoload.php'],
    ['config', 'config.php']
];
foreach ($includes as [$type, $file]) {
    $path = PathResolver::getIncludePath($type, $file);
    echo "   $type/$file: " . basename(dirname($path)) . "/$file\n";
    echo "      Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
}
echo "\n";

// 5. Test secure directory names (for shared hosting)
echo "5. Secure Directory Names:\n";
foreach ($types as $type) {
    $secureName = PathResolver::getSecureDirectoryName($type);
    echo "   $type -> $secureName\n";
}
echo "\n";

// 6. Test directory creation
echo "6. Directory Creation Test:\n";
$testDir = $projectRoot . '/test_' . uniqid();
$created = PathResolver::ensureDirectoryExists($testDir);
echo "   Test directory: $testDir\n";
echo "   Created: " . ($created ? 'YES' : 'NO') . "\n";
if ($created) {
    rmdir($testDir);
    echo "   Cleaned up\n";
}
echo "\n";

// 7. Performance test
echo "7. Performance Test (cached paths):\n";
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    PathResolver::getProjectRoot();
    PathResolver::getDataPath('uploads');
}
$time = (microtime(true) - $start) * 1000;
echo "   1000 calls completed in: " . round($time, 2) . "ms\n";
echo "   Average per call: " . round($time / 1000, 3) . "ms\n\n";

// 8. Test with different scenarios
echo "8. Scenario Tests:\n";
$_SERVER['DOCUMENT_ROOT'] = '/home/user/public_html';
PathResolver::clearCache();
echo "   Simulated cPanel setup: " . PathResolver::getProjectRoot() . "\n";

$_SERVER['DOCUMENT_ROOT'] = '/var/www/vhosts/example.com/httpdocs';
PathResolver::clearCache();
echo "   Simulated Plesk setup: " . PathResolver::getProjectRoot() . "\n";

// Reset
unset($_SERVER['DOCUMENT_ROOT']);
PathResolver::clearCache();

echo "\n=== Test Complete ===\n"; 