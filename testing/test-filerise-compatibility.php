<?php
/**
 * FileRise Shared Hosting Compatibility Checker
 * This script tests specific requirements for FileRise to work on shared hosting
 */

header('Content-Type: text/plain; charset=utf-8');

echo "FileRise Shared Hosting Compatibility Check\n";
echo "===========================================\n\n";

$passed = 0;
$failed = 0;
$warnings = 0;

function test($description, $result, $required = true) {
    global $passed, $failed, $warnings;
    
    $status = $result ? 'PASS' : ($required ? 'FAIL' : 'WARN');
    $symbol = $result ? '✓' : ($required ? '✗' : '⚠');
    
    echo sprintf("[%s] %s: %s\n", $symbol, $status, $description);
    
    if ($result) {
        $passed++;
    } elseif ($required) {
        $failed++;
    } else {
        $warnings++;
    }
}

// PHP Version
echo "PHP VERSION REQUIREMENTS:\n";
echo "------------------------\n";
$phpVersion = phpversion();
test("PHP 8.0 or higher (current: $phpVersion)", version_compare($phpVersion, '8.0.0', '>='));
echo "\n";

// Required Extensions
echo "REQUIRED EXTENSIONS:\n";
echo "-------------------\n";
$requiredExtensions = [
    'json' => 'JSON processing',
    'curl' => 'OAuth and external APIs',
    'zip' => 'ZIP file operations',
    'mbstring' => 'Multi-byte string support',
    'openssl' => 'Encryption and security'
];

foreach ($requiredExtensions as $ext => $desc) {
    test("$ext extension ($desc)", extension_loaded($ext));
}
echo "\n";

// Required Functions
echo "REQUIRED FUNCTIONS:\n";
echo "------------------\n";
$requiredFunctions = [
    'file_get_contents' => 'Reading files',
    'file_put_contents' => 'Writing files',
    'fopen' => 'File operations',
    'json_encode' => 'JSON encoding',
    'json_decode' => 'JSON decoding',
    'session_start' => 'Session management',
    'password_hash' => 'Password hashing',
    'password_verify' => 'Password verification'
];

foreach ($requiredFunctions as $func => $desc) {
    $available = function_exists($func) && !in_array($func, explode(',', ini_get('disable_functions')));
    test("$func() - $desc", $available);
}
echo "\n";

// Directory Structure Tests
echo "DIRECTORY STRUCTURE:\n";
echo "-------------------\n";
$currentDir = __DIR__;
$parentDir = dirname($currentDir);

// Test if we can work within current directory
test("Can create subdirectories", @mkdir($currentDir . '/test-dir', 0755) && @rmdir($currentDir . '/test-dir'));
test("Can write files in current directory", @file_put_contents($currentDir . '/test.txt', 'test') !== false && @unlink($currentDir . '/test.txt'));

// Test parent directory access (warning only)
test("Can access parent directory", @is_readable($parentDir), false);
echo "\n";

// FileRise Specific Requirements
echo "FILERISE SPECIFIC:\n";
echo "-----------------\n";

// Check if we can create FileRise directories
$fileriseDir = [
    'uploads' => 'File storage',
    'users' => 'User data',
    'metadata' => 'File metadata',
    'config' => 'Configuration'
];

foreach ($fileriseDir as $dir => $desc) {
    $testPath = $currentDir . '/' . $dir;
    $created = @mkdir($testPath, 0755);
    test("Can create '$dir' directory ($desc)", $created);
    if ($created) @rmdir($testPath);
}
echo "\n";

// Resource Limits
echo "RESOURCE LIMITS:\n";
echo "---------------\n";
$memoryLimit = ini_get('memory_limit');
$memoryBytes = convertToBytes($memoryLimit);
test("Memory limit >= 128MB (current: $memoryLimit)", $memoryBytes >= 128 * 1024 * 1024);

$uploadSize = ini_get('upload_max_filesize');
$uploadBytes = convertToBytes($uploadSize);
test("Upload size >= 32MB (current: $uploadSize)", $uploadBytes >= 32 * 1024 * 1024);

$postSize = ini_get('post_max_size');
$postBytes = convertToBytes($postSize);
test("POST size >= 33MB (current: $postSize)", $postBytes >= 33 * 1024 * 1024);

$execTime = ini_get('max_execution_time');
test("Execution time >= 30s (current: {$execTime}s)", $execTime == 0 || $execTime >= 30);
echo "\n";

// Session Support
echo "SESSION SUPPORT:\n";
echo "---------------\n";
$sessionPath = session_save_path() ?: sys_get_temp_dir();
test("Session save path writable ($sessionPath)", is_writable($sessionPath));
if (!isset($_SESSION)) {
    @session_start();
    $_SESSION['test'] = 'value';
    test("Can start and use sessions", isset($_SESSION['test']));
    session_destroy();
} else {
    test("Can start and use sessions", true);
}
echo "\n";

// Path Detection Methods
echo "PATH DETECTION:\n";
echo "--------------\n";
$pathMethods = [
    '__DIR__' => __DIR__,
    'getcwd()' => @getcwd(),
    'dirname(__FILE__)' => dirname(__FILE__),
    'realpath(".")' => @realpath('.')
];

$validPaths = 0;
foreach ($pathMethods as $method => $result) {
    if ($result) $validPaths++;
    echo sprintf("  %-20s: %s\n", $method, $result ?: 'FAILED');
}
test("At least one path detection method works", $validPaths > 0);
echo "\n";

// Summary
echo "COMPATIBILITY SUMMARY:\n";
echo "=====================\n";
echo "Passed: $passed tests\n";
echo "Failed: $failed tests\n";
echo "Warnings: $warnings tests\n\n";

if ($failed == 0) {
    echo "✅ FileRise should work on this hosting environment!\n";
    if ($warnings > 0) {
        echo "⚠️  Some features may be limited due to restrictions.\n";
    }
} else {
    echo "❌ FileRise cannot run on this hosting environment.\n";
    echo "   Please address the failed requirements above.\n";
}

// Recommendations
if ($failed > 0 || $warnings > 0) {
    echo "\nRECOMMENDATIONS:\n";
    echo "---------------\n";
    
    if (!@is_readable($parentDir)) {
        echo "- Store all FileRise data within the web root\n";
        echo "- Implement additional security measures for data files\n";
    }
    
    if ($memoryBytes < 128 * 1024 * 1024) {
        echo "- Request increased memory_limit from hosting provider\n";
        echo "- Implement chunked file processing\n";
    }
    
    if ($uploadBytes < 32 * 1024 * 1024) {
        echo "- Request increased upload_max_filesize\n";
        echo "- Use chunked uploads for large files\n";
    }
}

// Helper function
function convertToBytes($val) {
    $val = trim($val);
    $last = strtolower($val[strlen($val)-1]);
    $val = (int)$val;
    switch($last) {
        case 'g':
            $val *= 1024;
        case 'm':
            $val *= 1024;
        case 'k':
            $val *= 1024;
    }
    return $val;
} 