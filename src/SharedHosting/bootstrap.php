<?php
/**
 * Shared Hosting Bootstrap
 * 
 * This file initializes the shared hosting compatibility layer.
 * Include this at the beginning of config.php to enable shared hosting support.
 */

namespace FileRise\SharedHosting;

// Prevent direct access
if (!defined('FILERISE_ROOT') && !isset($GLOBALS['filerise_init'])) {
    $GLOBALS['filerise_init'] = true;
}

// Include PathResolver
require_once __DIR__ . '/PathResolver.php';

/**
 * Initialize shared hosting compatibility
 */
function initializeSharedHosting(): void {
    // 1. Define PROJECT_ROOT using PathResolver
    if (!defined('PROJECT_ROOT')) {
        define('PROJECT_ROOT', PathResolver::getProjectRoot());
    }
    
    // 2. Define data directory constants
    if (!defined('UPLOAD_DIR')) {
        define('UPLOAD_DIR', PathResolver::getDataPath('uploads'));
    }
    
    if (!defined('USERS_DIR')) {
        define('USERS_DIR', PathResolver::getDataPath('users'));
    }
    
    if (!defined('META_DIR')) {
        define('META_DIR', PathResolver::getDataPath('metadata'));
    }
    
    if (!defined('TRASH_DIR')) {
        define('TRASH_DIR', PathResolver::getDataPath('trash'));
    }
    
    // 3. Setup autoloader if not already done
    setupAutoloader();
    
    // 4. Apply runtime fixes for shared hosting
    applyRuntimeFixes();
    
    // 5. Ensure data directories exist with proper security
    ensureDataDirectorySecurity();
}

/**
 * Setup class autoloader for shared hosting
 */
function setupAutoloader(): void {
    // Check if Composer autoloader exists
    $vendorAutoload = PathResolver::getIncludePath('vendor', 'autoload.php');
    if (file_exists($vendorAutoload)) {
        require_once $vendorAutoload;
        return;
    }
    
    // Fallback: Simple autoloader for FileRise classes
    spl_autoload_register(function ($class) {
        // Remove namespace prefix
        $class = str_replace('FileRise\\', '', $class);
        $class = str_replace('\\', '/', $class);
        
        // Search paths
        $paths = [
            PROJECT_ROOT . '/src/' . $class . '.php',
            PROJECT_ROOT . '/src/models/' . $class . '.php',
            PROJECT_ROOT . '/src/controllers/' . $class . '.php',
            PROJECT_ROOT . '/src/webdav/' . $class . '.php',
        ];
        
        foreach ($paths as $path) {
            if (file_exists($path)) {
                require_once $path;
                return;
            }
        }
    });
}

/**
 * Apply runtime fixes for shared hosting environments
 */
function applyRuntimeFixes(): void {
    // 1. Error reporting adjustments
    if (PathResolver::isSharedHosting()) {
        // Suppress warnings about disabled functions
        $currentLevel = error_reporting();
        error_reporting($currentLevel & ~E_WARNING);
    }
    
    // 2. Set default timezone if not set
    if (!ini_get('date.timezone')) {
        @date_default_timezone_set('UTC');
    }
    
    // 3. Increase memory limit if possible
    $currentLimit = ini_get('memory_limit');
    if ($currentLimit && $currentLimit != '-1') {
        $limitBytes = convertToBytes($currentLimit);
        $desiredBytes = 128 * 1024 * 1024; // 128MB
        
        if ($limitBytes < $desiredBytes && function_exists('ini_set')) {
            @ini_set('memory_limit', '128M');
        }
    }
    
    // 4. Increase execution time if possible
    if (function_exists('set_time_limit')) {
        @set_time_limit(300); // 5 minutes
    }
    
    // 5. Enable output buffering for better error handling
    if (!ob_get_level()) {
        ob_start();
    }
}

/**
 * Ensure data directories exist with proper security measures
 */
function ensureDataDirectorySecurity(): void {
    $directories = [
        UPLOAD_DIR => true,  // needs .htaccess
        USERS_DIR => true,   // needs .htaccess
        META_DIR => true,    // needs .htaccess
        TRASH_DIR => true    // needs .htaccess
    ];
    
    foreach ($directories as $dir => $needsSecurity) {
        // Create directory if needed
        PathResolver::ensureDirectoryExists($dir);
        
        // Add security files for shared hosting
        if ($needsSecurity && PathResolver::isSharedHosting()) {
            createSecurityFiles($dir);
        }
    }
}

/**
 * Create security files (.htaccess, index.html) for a directory
 */
function createSecurityFiles(string $directory): void {
    // 1. Create .htaccess to deny direct access
    $htaccessFile = $directory . '/.htaccess';
    if (!file_exists($htaccessFile)) {
        $htaccessContent = <<<HTACCESS
# Deny all direct access to files in this directory
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>

# Disable directory listing
Options -Indexes

# Disable script execution
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|htm|html|shtml|sh|cgi)$">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</FilesMatch>
HTACCESS;
        
        @file_put_contents($htaccessFile, $htaccessContent);
    }
    
    // 2. Create index.html to prevent directory listing
    $indexFile = $directory . '/index.html';
    if (!file_exists($indexFile)) {
        $indexContent = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    <h1>Access Denied</h1>
    <p>You don't have permission to access this directory.</p>
</body>
</html>
HTML;
        
        @file_put_contents($indexFile, $indexContent);
    }
}

/**
 * Convert memory limit string to bytes
 */
function convertToBytes(string $value): int {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int)$value;
    
    switch($last) {
        case 'g':
            $value *= 1024;
        case 'm':
            $value *= 1024;
        case 'k':
            $value *= 1024;
    }
    
    return $value;
}

/**
 * Get a safe include path that works with open_basedir
 * 
 * @param string $file File to include
 * @return string Safe path
 */
function safeInclude(string $file): string {
    // If it's already an absolute path and accessible, return it
    if (file_exists($file)) {
        return $file;
    }
    
    // Try relative to project root
    $path = PROJECT_ROOT . '/' . ltrim($file, '/');
    if (file_exists($path)) {
        return $path;
    }
    
    // Return original (will fail, but error is descriptive)
    return $file;
}

// Initialize when included
initializeSharedHosting(); 