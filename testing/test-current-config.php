<?php
/**
 * Test current FileRise config.php with shared hosting restrictions
 */

echo "Testing Current FileRise Configuration\n";
echo "=====================================\n\n";

// Simulate being in a subdirectory (like public/ or public_html/)
$testDir = __DIR__ . '/test-current';
chdir($testDir);

echo "Current directory: " . getcwd() . "\n";
echo "Simulating config.php load from: $testDir\n\n";

// Test 1: Try to use dirname(__DIR__) with open_basedir
echo "TEST 1: PROJECT_ROOT Definition\n";
echo "-------------------------------\n";
try {
    // This simulates what config.php does
    $projectRoot = dirname(__DIR__);
    echo "dirname(__DIR__) = $projectRoot\n";
    echo "✓ Can access parent directory\n";
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 2: Load actual config and see what happens
echo "TEST 2: Loading config.php\n";
echo "--------------------------\n";
try {
    // Prevent headers from being sent
    ob_start();
    
    // Try to include the config
    include 'config.php';
    
    ob_end_clean();
    
    echo "✓ Config loaded successfully\n";
    echo "Constants defined:\n";
    echo "  PROJECT_ROOT: " . (defined('PROJECT_ROOT') ? PROJECT_ROOT : 'NOT DEFINED') . "\n";
    echo "  UPLOAD_DIR: " . (defined('UPLOAD_DIR') ? UPLOAD_DIR : 'NOT DEFINED') . "\n";
    echo "  USERS_DIR: " . (defined('USERS_DIR') ? USERS_DIR : 'NOT DEFINED') . "\n";
    echo "  META_DIR: " . (defined('META_DIR') ? META_DIR : 'NOT DEFINED') . "\n";
} catch (Exception $e) {
    echo "✗ Error loading config: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 3: Try to access the directories
echo "TEST 3: Directory Access\n";
echo "------------------------\n";
$dirs = [
    'UPLOAD_DIR' => defined('UPLOAD_DIR') ? UPLOAD_DIR : '/var/www/uploads/',
    'USERS_DIR' => defined('USERS_DIR') ? USERS_DIR : '/var/www/users/',
    'META_DIR' => defined('META_DIR') ? META_DIR : '/var/www/metadata/'
];

foreach ($dirs as $name => $path) {
    echo "$name ($path):\n";
    
    // Check if readable
    if (@is_readable($path)) {
        echo "  ✓ Readable\n";
    } else {
        echo "  ✗ Not readable\n";
    }
    
    // Check if writable
    if (@is_writable($path)) {
        echo "  ✓ Writable\n";
    } else {
        echo "  ✗ Not writable\n";
    }
    
    // Check if exists
    if (@file_exists($path)) {
        echo "  ✓ Exists\n";
    } else {
        echo "  ✗ Does not exist\n";
    }
    echo "\n";
}

// Test 4: Simulate setup check
echo "TEST 4: Setup Detection\n";
echo "-----------------------\n";
$usersFile = (defined('USERS_DIR') ? USERS_DIR : '/var/www/users/') . 'users.txt';
echo "Checking users file: $usersFile\n";

// This is what AuthController does
if (!@file_exists($usersFile)) {
    echo "✗ Users file does not exist - would trigger setup mode\n";
} else {
    $content = @file_get_contents($usersFile);
    if ($content === false) {
        echo "✗ Cannot read users file\n";
    } elseif (trim($content) === '') {
        echo "✗ Users file is empty - would trigger setup mode\n";
    } else {
        echo "✓ Users file exists and has content\n";
    }
}
echo "\n";

// Summary
echo "SUMMARY\n";
echo "-------\n";
echo "This configuration will NOT work on shared hosting because:\n";
echo "1. Hardcoded paths (/var/www/*) don't exist\n";
echo "2. No fallback for inaccessible directories\n";
echo "3. Setup detection fails when directories are inaccessible\n";
echo "4. All file operations will fail\n"; 