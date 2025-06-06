<?php
/**
 * FileRise Shared Hosting Environment Checker
 * This script helps verify PHP restrictions and environment settings
 */

header('Content-Type: text/plain; charset=utf-8');

echo "FileRise Shared Hosting Environment Check\n";
echo "=========================================\n\n";

// PHP Version
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n\n";

// Memory and Execution Limits
echo "RESOURCE LIMITS:\n";
echo "----------------\n";
echo "memory_limit: " . ini_get('memory_limit') . "\n";
echo "max_execution_time: " . ini_get('max_execution_time') . " seconds\n";
echo "max_input_time: " . ini_get('max_input_time') . " seconds\n";
echo "max_input_vars: " . ini_get('max_input_vars') . "\n\n";

// File Upload Limits
echo "FILE UPLOAD LIMITS:\n";
echo "-------------------\n";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n\n";

// Path Restrictions
echo "PATH RESTRICTIONS:\n";
echo "------------------\n";
$openBasedir = ini_get('open_basedir');
if ($openBasedir) {
    echo "open_basedir: " . $openBasedir . "\n";
    echo "Allowed paths:\n";
    $paths = explode(PATH_SEPARATOR, $openBasedir);
    foreach ($paths as $path) {
        echo "  - " . $path . "\n";
    }
} else {
    echo "open_basedir: Not set (no restrictions)\n";
}
echo "\n";

// Current paths
echo "CURRENT PATHS:\n";
echo "--------------\n";
echo "Script path: " . __FILE__ . "\n";
echo "Script dir: " . __DIR__ . "\n";
echo "Working dir: " . getcwd() . "\n";
if (isset($_SERVER['DOCUMENT_ROOT'])) {
    echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
}
echo "\n";

// Disabled Functions
echo "DISABLED FUNCTIONS:\n";
echo "-------------------\n";
$disabled = ini_get('disable_functions');
if ($disabled) {
    $functions = explode(',', $disabled);
    $functions = array_map('trim', $functions);
    sort($functions);
    
    // Group by category
    $categories = [
        'exec' => ['exec', 'system', 'passthru', 'shell_exec', 'proc_open', 'popen'],
        'file' => ['fopen', 'fread', 'fwrite', 'file_get_contents', 'file_put_contents', 'readfile', 'fpassthru'],
        'network' => ['fsockopen', 'pfsockopen', 'stream_socket_client', 'stream_socket_server', 'curl_exec', 'curl_multi_exec'],
        'system' => ['dl', 'symlink', 'link', 'chown', 'chmod', 'chgrp'],
        'info' => ['phpinfo', 'php_uname', 'getmyuid', 'getmypid'],
        'other' => []
    ];
    
    foreach ($functions as $func) {
        $categorized = false;
        foreach ($categories as $cat => &$list) {
            if ($cat !== 'other' && in_array($func, $list)) {
                $categorized = true;
                break;
            }
        }
        if (!$categorized && !in_array($func, $categories['other'])) {
            $categories['other'][] = $func;
        }
    }
    
    foreach ($categories as $cat => $funcs) {
        $inCategory = array_intersect($functions, $cat === 'other' ? $categories['other'] : array_values($categories[$cat]));
        if (!empty($inCategory)) {
            echo ucfirst($cat) . " functions: " . implode(', ', $inCategory) . "\n";
        }
    }
} else {
    echo "No functions disabled\n";
}
echo "\n";

// Test File Operations
echo "FILE OPERATION TESTS:\n";
echo "---------------------\n";

// Test reading
$testFile = __DIR__ . '/test-read.txt';
file_put_contents($testFile, "Test content");
echo "Can read files: " . (is_readable($testFile) ? "YES" : "NO") . "\n";
echo "Can write files: " . (is_writable(__DIR__) ? "YES" : "NO") . "\n";
@unlink($testFile);

// Test directory operations
echo "Can create directories: " . (is_writable(__DIR__) ? "YES" : "NO") . "\n";
echo "Can list directories: " . (function_exists('opendir') && !in_array('opendir', explode(',', ini_get('disable_functions'))) ? "YES" : "NO") . "\n";

// Test common functions
$functionsToTest = [
    'file_get_contents' => 'File reading',
    'file_put_contents' => 'File writing',
    'fopen' => 'File opening',
    'curl_init' => 'cURL',
    'json_encode' => 'JSON encoding',
    'zip_open' => 'ZIP operations',
    'session_start' => 'Sessions'
];

echo "\nFunction availability:\n";
foreach ($functionsToTest as $func => $desc) {
    $available = function_exists($func) && !in_array($func, explode(',', ini_get('disable_functions')));
    echo sprintf("  %-20s: %s\n", $desc, $available ? "YES" : "NO");
}

// Test path access
echo "\nPATH ACCESS TESTS:\n";
echo "------------------\n";
$pathsToTest = [
    __DIR__,
    dirname(__DIR__),
    '/tmp',
    sys_get_temp_dir()
];

foreach ($pathsToTest as $path) {
    $readable = @is_readable($path);
    $writable = @is_writable($path);
    echo sprintf("%-30s: Read: %s, Write: %s\n", $path, $readable ? "YES" : "NO", $writable ? "YES" : "NO");
}

// Extensions
echo "\nLOADED EXTENSIONS:\n";
echo "------------------\n";
$requiredExtensions = ['json', 'curl', 'zip', 'mbstring', 'openssl'];
foreach ($requiredExtensions as $ext) {
    echo sprintf("%-15s: %s\n", $ext, extension_loaded($ext) ? "Loaded" : "NOT LOADED");
}

// Additional info
echo "\nADDITIONAL INFO:\n";
echo "----------------\n";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? "ON" : "OFF") . "\n";
echo "allow_url_include: " . (ini_get('allow_url_include') ? "ON" : "OFF") . "\n";
echo "display_errors: " . (ini_get('display_errors') ? "ON" : "OFF") . "\n";
echo "error_reporting: " . ini_get('error_reporting') . "\n";

echo "\nEnvironment check complete.\n"; 