<?php
/**
 * Test script for shared hosting configuration
 */

echo "=== Shared Hosting Config Test ===\n\n";

// Force shared hosting mode for testing
putenv('FILERISE_SHARED_HOSTING=true');

// Include the new config
require_once __DIR__ . '/../config/config.php';

echo "1. Constants Check:\n";
echo "   PROJECT_ROOT: " . PROJECT_ROOT . "\n";
echo "   UPLOAD_DIR: " . UPLOAD_DIR . "\n";
echo "   USERS_DIR: " . USERS_DIR . "\n";
echo "   META_DIR: " . META_DIR . "\n";
echo "   TRASH_DIR: " . TRASH_DIR . "\n\n";

echo "2. Directory Status:\n";
$dirs = ['UPLOAD_DIR' => UPLOAD_DIR, 'USERS_DIR' => USERS_DIR, 'META_DIR' => META_DIR, 'TRASH_DIR' => TRASH_DIR];
foreach ($dirs as $name => $path) {
    echo "   $name:\n";
    echo "     Exists: " . (is_dir($path) ? 'YES' : 'NO') . "\n";
    echo "     Writable: " . (is_writable($path) ? 'YES' : 'NO') . "\n";
    
    // Check for security files
    if (is_dir($path)) {
        $htaccess = file_exists($path . '.htaccess') ? 'YES' : 'NO';
        $indexHtml = file_exists($path . 'index.html') ? 'YES' : 'NO';
        echo "     .htaccess: $htaccess\n";
        echo "     index.html: $indexHtml\n";
    }
}
echo "\n";

echo "3. Session Check:\n";
echo "   Session status: " . session_status() . " (1=disabled, 2=active)\n";
echo "   Session ID: " . (session_id() ?: 'none') . "\n";
echo "   CSRF token exists: " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO') . "\n\n";

echo "4. Include Test:\n";
// Test if we can include files using the safe include
if (function_exists('FileRise\SharedHosting\safeInclude')) {
    $testFile = 'src/controllers/AuthController.php';
    $includePath = \FileRise\SharedHosting\safeInclude($testFile);
    echo "   Safe include for '$testFile': " . basename(dirname($includePath)) . "/" . basename($includePath) . "\n";
    echo "   File exists: " . (file_exists($includePath) ? 'YES' : 'NO') . "\n";
} else {
    echo "   Safe include function not available\n";
}
echo "\n";

echo "5. Compatibility Mode:\n";
echo "   Using shared hosting: " . ($useSharedHosting ? 'YES' : 'NO') . "\n";
echo "   PathResolver loaded: " . (class_exists('FileRise\SharedHosting\PathResolver') ? 'YES' : 'NO') . "\n";

// Clean up
putenv('FILERISE_SHARED_HOSTING');

echo "\n=== Test Complete ===\n"; 