<?php
// config.php
// Universal, environment-aware configuration for FileRise

// The bootstrap file handles all security headers, path definitions,
// constant definitions, session handling, and auto-login logic.
require_once __DIR__ . '/../src/SharedHosting/bootstrap.php';

// Define remaining global constants
define('TIMEZONE',      'America/New_York');
define('DATE_TIME_FORMAT','m/d/y  h:iA');
define('TOTAL_UPLOAD_SIZE','5G');
define('REGEX_FOLDER_NAME','/^(?!^(?:CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$)(?!.*[. ]$)(?:[^<>:"\/\\\\|?*\x00-\x1F]{1,255})(?:[\/\\\\][^<>:"\/\\\\|?*\x00-\x1F]{1,255})*$/xu');
define('PATTERN_FOLDER_NAME','[\p{L}\p{N}_\-\s\/\\\\]+');
define('REGEX_FILE_NAME', '/^[^\x00-\x1F\/\\\\]{1,255}$/u');
define('REGEX_USER',       '/^[\p{L}\p{N}_\- ]+$/u');


date_default_timezone_set(TIMEZONE);

// Load encryption key
$envKey = getenv('PERSISTENT_TOKENS_KEY');
if ($envKey === false || $envKey === '') {
    $encryptionKey = 'default_please_change_this_key';
    error_log('WARNING: Using default encryption key. Please set PERSISTENT_TOKENS_KEY in your environment.');
} else {
    $encryptionKey = $envKey;
}
$GLOBALS['encryptionKey'] = $encryptionKey;

// The rest of the logic (session, auto-login, etc.) is now in bootstrap.php
// This leaves the config file clean and focused on defining basic constants.

// Share URL fallback
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}";
define('SHARE_URL', getenv('SHARE_URL') ?: $baseUrl . '/api/file/share.php'); 