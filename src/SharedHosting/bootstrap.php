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

/**
 * Initialize session handling and auto-login logic
 */
function initializeSession(): void {
    global $encryptionKey;

    // Determine HTTPS usage
    $envSecure = getenv('SECURE');
    $secure = ($envSecure !== false)
        ? filter_var($envSecure, FILTER_VALIDATE_BOOLEAN)
        : (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // Choose session lifetime
    $defaultSession = 7200; // 2 hours
    $persistentDays = 30 * 24 * 60 * 60; // 30 days
    $sessionLifetime = isset($_COOKIE['remember_me_token']) ? $persistentDays : $defaultSession;

    session_set_cookie_params([
        'lifetime' => $sessionLifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    ini_set('session.gc_maxlifetime', (string)$sessionLifetime);

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // CSRF token
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Auto-login via persistent token
    if (empty($_SESSION["authenticated"]) && !empty($_COOKIE['remember_me_token'])) {
        $tokFile = PathResolver::getDataPath('users') . 'persistent_tokens.json';
        if (file_exists($tokFile)) {
            $enc = file_get_contents($tokFile);
            $dec = decryptData($enc, $encryptionKey);
            $tokens = json_decode($dec, true) ?: [];
            $token = $_COOKIE['remember_me_token'];

            if (!empty($tokens[$token]) && $tokens[$token]['expiry'] >= time()) {
                $data = $tokens[$token];
                $_SESSION["authenticated"] = true;
                $_SESSION["username"]      = $data["username"];
                $_SESSION["folderOnly"]    = loadUserPermissions($data["username"]);
                $_SESSION["isAdmin"]       = !empty($data["isAdmin"]);
            } elseif (!empty($tokens[$token])) {
                unset($tokens[$token]);
                file_put_contents($tokFile, encryptData(json_encode($tokens, JSON_PRETTY_PRINT), $encryptionKey), LOCK_EX);
                setcookie('remember_me_token', '', time() - 3600, '/', '', $secure, true);
            }
        }
    }

    // Proxy-only auto-login
    $adminConfigFile = PathResolver::getDataPath('users') . 'adminConfig.json';
    $cfgAuthBypass = false;
    $cfgAuthHeader = 'HTTP_X_REMOTE_USER';

    if (file_exists($adminConfigFile)) {
        $encrypted = file_get_contents($adminConfigFile);
        $decrypted = decryptData($encrypted, $encryptionKey);
        $adminCfg  = json_decode($decrypted, true) ?: [];
        $loginOpts = $adminCfg['loginOptions'] ?? [];
        $cfgAuthBypass = !empty($loginOpts['authBypass']);
        $hdr = trim($loginOpts['authHeaderName'] ?? 'X-Remote-User');
        $cfgAuthHeader = 'HTTP_' . strtoupper(str_replace('-', '_', $hdr));
    }

    if ($cfgAuthBypass && !empty($_SERVER[$cfgAuthHeader])) {
        if (empty($_SESSION['authenticated'])) {
            session_regenerate_id(true);
        }
        $username = $_SERVER[$cfgAuthHeader];
        $_SESSION['authenticated'] = true;
        $_SESSION['username']      = $username;
        require_once PROJECT_ROOT . '/src/models/AuthModel.php';
        $_SESSION['isAdmin'] = (\AuthModel::getUserRole($username) === '1');
        $perms = loadUserPermissions($username) ?: [];
        $_SESSION['folderOnly']    = $perms['folderOnly']    ?? false;
        $_SESSION['readOnly']      = $perms['readOnly']      ?? false;
        $_SESSION['disableUpload'] = $perms['disableUpload'] ?? false;
    }
}

// ============== HELPER FUNCTIONS ==============

/**
 * Encryption helper to encrypt data.
 */
function encryptData($data, $encryptionKey)
{
    $cipher = 'AES-256-CBC';
    $ivlen  = openssl_cipher_iv_length($cipher);
    $iv     = openssl_random_pseudo_bytes($ivlen);
    $ct     = openssl_encrypt($data, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $ct);
}

/**
 * Encryption helper to decrypt data.
 */
function decryptData($encryptedData, $encryptionKey)
{
    $cipher = 'AES-256-CBC';
    $data   = base64_decode($encryptedData);
    $ivlen  = openssl_cipher_iv_length($cipher);
    $iv     = substr($data, 0, $ivlen);
    $ct     = substr($data, $ivlen);
    return openssl_decrypt($ct, $cipher, $encryptionKey, OPENSSL_RAW_DATA, $iv);
}

/**
 * Helper to load JSON permissions.
 */
function loadUserPermissions($username)
{
    global $encryptionKey;
    $permissionsFile = PathResolver::getDataPath('users') . 'userPermissions.json';
    if (file_exists($permissionsFile)) {
        $content = file_get_contents($permissionsFile);
        $decrypted = decryptData($content, $encryptionKey);
        $json = ($decrypted !== false) ? $decrypted : $content;
        $perms = json_decode($json, true);
        if (is_array($perms) && isset($perms[$username])) {
            return !empty($perms[$username]) ? $perms[$username] : false;
        }
    }
    return false;
}

// Initialize when included
initializeSharedHosting();
initializeSession(); 