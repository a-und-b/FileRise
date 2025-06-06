<?php
/**
 * FileRise Shared Hosting Restriction Tester
 * Tests specific operations that might fail on shared hosting
 */

header('Content-Type: text/plain; charset=utf-8');

echo "FileRise Shared Hosting Restriction Tests\n";
echo "=========================================\n\n";

$results = [];
$currentDir = __DIR__;

// Test 1: Parent Directory Access
echo "TEST 1: Parent Directory Access\n";
echo "-------------------------------\n";
$parentDir = dirname($currentDir);
$canAccessParent = @is_readable($parentDir);
$results['parent_dir_access'] = $canAccessParent;
echo "Can access parent directory ($parentDir): " . ($canAccessParent ? "YES" : "NO") . "\n";

if (!$canAccessParent) {
    $error = error_get_last();
    if ($error) {
        echo "Error: " . $error['message'] . "\n";
    }
}
echo "\n";

// Test 2: Create directories outside current directory
echo "TEST 2: Directory Creation\n";
echo "--------------------------\n";
$testDirs = [
    $currentDir . '/test-subdir' => 'Subdirectory',
    $parentDir . '/test-sibling' => 'Sibling directory',
    '/tmp/filerise-test' => 'Temp directory'
];

foreach ($testDirs as $dir => $desc) {
    $created = @mkdir($dir, 0755, true);
    echo sprintf("%-20s: %s\n", $desc, $created ? "SUCCESS" : "FAILED");
    if ($created) {
        @rmdir($dir);
    }
}
echo "\n";

// Test 3: File operations across directories
echo "TEST 3: Cross-Directory File Operations\n";
echo "---------------------------------------\n";
$testFile = $currentDir . '/test-file.txt';
@file_put_contents($testFile, "Test content");

// Try to copy to parent
$parentCopy = $parentDir . '/test-copy.txt';
$copied = @copy($testFile, $parentCopy);
echo "Copy to parent dir: " . ($copied ? "SUCCESS" : "FAILED") . "\n";
if ($copied) @unlink($parentCopy);

// Try to move within current dir
$movedFile = $currentDir . '/test-moved.txt';
$moved = @rename($testFile, $movedFile);
echo "Move within current dir: " . ($moved ? "SUCCESS" : "FAILED") . "\n";
if ($moved) @unlink($movedFile);
else @unlink($testFile);
echo "\n";

// Test 4: Path resolution methods
echo "TEST 4: Path Resolution Methods\n";
echo "-------------------------------\n";
$pathTests = [
    '__DIR__' => __DIR__,
    'dirname(__FILE__)' => dirname(__FILE__),
    'realpath(".")' => @realpath('.'),
    'getcwd()' => @getcwd(),
    '$_SERVER["SCRIPT_FILENAME"]' => $_SERVER['SCRIPT_FILENAME'] ?? 'Not set',
    '$_SERVER["DOCUMENT_ROOT"]' => $_SERVER['DOCUMENT_ROOT'] ?? 'Not set'
];

foreach ($pathTests as $method => $result) {
    echo sprintf("%-30s: %s\n", $method, $result ?: 'FAILED');
}
echo "\n";

// Test 5: Include path testing
echo "TEST 5: Include Path Testing\n";
echo "----------------------------\n";
$includePath = get_include_path();
echo "Current include_path: $includePath\n";

// Try to set include path
$newPath = $currentDir . PATH_SEPARATOR . $includePath;
$setPath = @set_include_path($newPath);
echo "Can modify include_path: " . ($setPath ? "YES" : "NO") . "\n";
echo "\n";

// Test 6: Temp directory access
echo "TEST 6: Temporary Directory Access\n";
echo "----------------------------------\n";
$tempDir = sys_get_temp_dir();
echo "System temp dir: $tempDir\n";

$tempFile = tempnam($tempDir, 'filerise');
if ($tempFile) {
    echo "Can create temp files: YES\n";
    echo "Temp file created: $tempFile\n";
    @unlink($tempFile);
} else {
    echo "Can create temp files: NO\n";
}
echo "\n";

// Test 7: Session handling
echo "TEST 7: Session Handling\n";
echo "------------------------\n";
if (!isset($_SESSION)) {
    $sessionStarted = @session_start();
    echo "Can start sessions: " . ($sessionStarted ? "YES" : "NO") . "\n";
    if ($sessionStarted) {
        $_SESSION['test'] = 'value';
        echo "Can write to session: " . (isset($_SESSION['test']) ? "YES" : "NO") . "\n";
        session_destroy();
    }
} else {
    echo "Session already started\n";
}
echo "\n";

// Test 8: Large file handling
echo "TEST 8: Large File Handling\n";
echo "---------------------------\n";
$memoryLimit = ini_get('memory_limit');
echo "Memory limit: $memoryLimit\n";

// Try to allocate 50MB
$size = 50 * 1024 * 1024; // 50MB
$allocated = @str_repeat('x', $size);
echo "Can allocate 50MB: " . (strlen($allocated) == $size ? "YES" : "NO") . "\n";
unset($allocated);
echo "\n";

// Summary
echo "SUMMARY\n";
echo "-------\n";
$passed = 0;
$failed = 0;
foreach ($results as $test => $result) {
    if ($result) $passed++;
    else $failed++;
}
echo "Tests passed: $passed\n";
echo "Tests failed: $failed\n";

// Recommendations
echo "\nRECOMMENDATIONS\n";
echo "---------------\n";
if (!$canAccessParent) {
    echo "- Use only relative paths within the application directory\n";
    echo "- Store all data within the web root\n";
    echo "- Implement proper security for files within web root\n";
}

echo "\nTest complete.\n"; 