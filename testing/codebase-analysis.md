# FileRise Shared Hosting Compatibility Analysis

## Executive Summary

The current FileRise codebase has several critical issues that prevent it from working on shared hosting environments:

1. **Hardcoded absolute paths** that don't exist on shared hosting
2. **Parent directory access** using `dirname(__DIR__)` which violates open_basedir
3. **Setup wizard logic** that fails when it can't access hardcoded directories
4. **Extensive use of PROJECT_ROOT** constant throughout the codebase

## Critical Issues (Must Fix)

### 1. Path Configuration (config/config.php)

**Current Code:**
```php
// Line 20
define('PROJECT_ROOT', dirname(__DIR__));
// Lines 21-25
define('UPLOAD_DIR',    '/var/www/uploads/');
define('USERS_DIR',     '/var/www/users/');
define('META_DIR',      '/var/www/metadata/');
define('TRASH_DIR',     UPLOAD_DIR . 'trash/');
```

**Problems:**
- `dirname(__DIR__)` fails with open_basedir restrictions
- Hardcoded `/var/www/` paths don't exist on shared hosting
- No flexibility for different directory structures

**Impact:** Complete failure to initialize - nothing works

**Files Affected:** 
- config/config.php
- Every PHP file that includes config.php (entire application)

### 2. PROJECT_ROOT Usage

**Scope:** Used in 70+ files for:
- Including model files: `require_once PROJECT_ROOT . '/src/models/...'`
- Including controllers: `require_once PROJECT_ROOT . '/src/controllers/...'`
- Loading vendor autoload: `require_once PROJECT_ROOT . '/vendor/autoload.php'`
- Loading config: `require_once PROJECT_ROOT . '/config/config.php'`

**Impact:** Every API endpoint and controller fails to load dependencies

### 3. Setup Wizard Detection (src/controllers/AuthController.php)

**Current Code (Lines 391-395):**
```php
$usersFile = USERS_DIR . USERS_FILE;
// 2) Setup mode?
if (!file_exists($usersFile) || trim(file_get_contents($usersFile)) === '') {
    error_log("checkAuth: setup mode");
    echo json_encode(['setup' => true]);
    exit();
}
```

**Problems:**
- Tries to access `/var/www/users/users.txt`
- Fails before checking if file exists
- No error handling for inaccessible paths

**Impact:** Users get stuck in setup loop or see errors

## Important Issues (Major Features)

### 4. Directory Constants Usage

**Scope:** Used throughout for file operations:
- `UPLOAD_DIR` - 40+ occurrences
- `USERS_DIR` - 20+ occurrences  
- `META_DIR` - 30+ occurrences
- `TRASH_DIR` - 10+ occurrences

**Key Files:**
- All model files (FileModel.php, UserModel.php, etc.)
- All controllers
- WebDAV implementation

**Impact:** All file operations fail

### 5. File Include Patterns

**Current Pattern:**
```php
require_once PROJECT_ROOT . '/src/models/SomeModel.php';
```

**Problem:** Assumes ability to traverse to parent directories

**Better Pattern Needed:**
```php
require_once PathResolver::getModelPath('SomeModel.php');
```

## Security Implications

### Moving Directories Into Web Root

**Current Structure (Secure):**
```
/var/www/
├── project/
│   └── public/  (web root)
├── uploads/     (outside web root)
├── users/       (outside web root)
└── metadata/    (outside web root)
```

**Shared Hosting Structure (Less Secure):**
```
public_html/     (web root)
├── index.html
├── api/
├── uploads/     (INSIDE web root - security risk!)
├── users/       (INSIDE web root - security risk!)
└── metadata/    (INSIDE web root - security risk!)
```

**Required Mitigations:**
1. Add .htaccess to protect sensitive directories
2. Use randomized directory names
3. Implement application-level access control
4. Consider encrypting sensitive files

## Proposed Solutions

### 1. Dynamic Path Resolution System

Create `src/SharedHosting/PathResolver.php`:
```php
class PathResolver {
    private static $projectRoot = null;
    private static $paths = [];
    
    public static function getProjectRoot() {
        if (self::$projectRoot === null) {
            self::$projectRoot = self::detectProjectRoot();
        }
        return self::$projectRoot;
    }
    
    private static function detectProjectRoot() {
        // Try multiple strategies
        $strategies = [
            __DIR__ . '/../..',                    // From src/SharedHosting
            dirname($_SERVER['DOCUMENT_ROOT']),     // Parent of doc root
            $_SERVER['DOCUMENT_ROOT'],              // Doc root itself
            getcwd()                                // Current directory
        ];
        
        foreach ($strategies as $path) {
            if (self::isValidProjectRoot($path)) {
                return realpath($path);
            }
        }
        
        // Fallback to current directory
        return __DIR__;
    }
}
```

### 2. Configuration Refactor

Replace hardcoded paths with dynamic detection:
```php
// Instead of:
define('UPLOAD_DIR', '/var/www/uploads/');

// Use:
define('UPLOAD_DIR', PathResolver::getDataPath('uploads'));
```

### 3. Include Path Solution

Add autoloader or helper function:
```php
function requireModel($modelName) {
    $path = PathResolver::getModelPath($modelName);
    require_once $path;
}
```

## File Count Summary

**Files Needing Modification:**
- 1 configuration file (config.php)
- 70+ API endpoint files
- 6 model files
- 6 controller files
- 2 WebDAV files
- Total: ~85-90 files need updates

**Modification Types:**
1. Replace `PROJECT_ROOT` usage (70+ files)
2. Replace directory constant usage (40+ files)
3. Update include/require statements (85+ files)
4. Add path resolution logic (new files)

## Implementation Priority

### Phase 1: Core Infrastructure
1. Create PathResolver class
2. Update config.php to use dynamic paths
3. Create compatibility layer for constants

### Phase 2: API Updates
1. Update all API files to use new include method
2. Test each endpoint with restrictions

### Phase 3: Models & Controllers
1. Update file operation paths
2. Ensure all paths are relative/resolved

### Phase 4: Security Hardening
1. Add .htaccess files
2. Implement additional access controls
3. Document security considerations

## Testing Requirements

Each change must be tested:
1. With open_basedir restrictions
2. In different directory structures
3. With both existing and new installations
4. For security implications

## Estimated Effort

- **High Complexity:** Path resolution system (1-2 days)
- **Medium Complexity:** Config refactor (0.5 days)
- **Low Complexity but High Volume:** File updates (2-3 days)
- **Testing:** (1-2 days)
- **Total:** 5-8 days of development

## Next Steps

1. Implement PathResolver class
2. Create compatibility constants
3. Start with config.php changes
4. Update one API endpoint as proof of concept
5. Systematically update remaining files 