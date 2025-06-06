<?php
/**
 * PathResolver - Dynamic path resolution for shared hosting compatibility
 * 
 * This class provides flexible path detection that works within open_basedir
 * restrictions and various hosting environments.
 */

namespace FileRise\SharedHosting;

class PathResolver
{
    /**
     * Cached paths to avoid repeated detection
     */
    private static $cache = [];
    
    /**
     * Project root path once detected
     */
    private static $projectRoot = null;
    
    /**
     * Data directory names
     */
    private const DATA_DIRS = [
        'uploads' => 'uploads',
        'users' => 'users',
        'metadata' => 'metadata',
        'trash' => 'uploads/trash'
    ];
    
    /**
     * Get the project root directory
     * 
     * @return string The project root path
     */
    public static function getProjectRoot(): string
    {
        if (self::$projectRoot !== null) {
            return self::$projectRoot;
        }
        
        // Try multiple detection strategies
        $strategies = [
            // Strategy 1: Environment variable (if set)
            [self::class, 'detectFromEnvironment'],
            
            // Strategy 2: Look for known files (composer.json, etc.)
            [self::class, 'detectFromKnownFiles'],
            
            // Strategy 3: Based on current file location
            [self::class, 'detectFromCurrentLocation'],
            
            // Strategy 4: Document root based
            [self::class, 'detectFromDocumentRoot'],
            
            // Strategy 5: Working directory
            [self::class, 'detectFromWorkingDirectory']
        ];
        
        foreach ($strategies as $strategy) {
            $path = call_user_func($strategy);
            if ($path && self::isValidProjectRoot($path)) {
                self::$projectRoot = $path;
                return self::$projectRoot;
            }
        }
        
        // Fallback: use current directory
        self::$projectRoot = __DIR__;
        return self::$projectRoot;
    }
    
    /**
     * Get a data directory path (uploads, users, metadata)
     * 
     * @param string $type Directory type (uploads, users, metadata, trash)
     * @return string The directory path
     */
    public static function getDataPath(string $type): string
    {
        $cacheKey = "data_$type";
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        $dirName = self::DATA_DIRS[$type] ?? $type;
        $root = self::getProjectRoot();
        
        // Try different locations
        $attempts = [
            // 1. Traditional location (outside web root)
            dirname($root) . DIRECTORY_SEPARATOR . $dirName,
            
            // 2. Inside project root (shared hosting)
            $root . DIRECTORY_SEPARATOR . $dirName,
            
            // 3. Inside data subdirectory
            $root . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $dirName,
            
            // 4. Inside storage subdirectory
            $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $dirName,
            
            // 5. Inside public if that's our root
            $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $dirName
        ];
        
        // Find first accessible location
        foreach ($attempts as $path) {
            if (self::isAccessibleDirectory($path)) {
                self::$cache[$cacheKey] = $path . DIRECTORY_SEPARATOR;
                return self::$cache[$cacheKey];
            }
        }
        
        // Create in project root if nothing exists
        $defaultPath = $root . DIRECTORY_SEPARATOR . $dirName;
        if (self::ensureDirectoryExists($defaultPath)) {
            self::$cache[$cacheKey] = $defaultPath . DIRECTORY_SEPARATOR;
            return self::$cache[$cacheKey];
        }
        
        // Last resort: temp directory
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'filerise' . DIRECTORY_SEPARATOR . $dirName;
        self::ensureDirectoryExists($tempPath);
        self::$cache[$cacheKey] = $tempPath . DIRECTORY_SEPARATOR;
        return self::$cache[$cacheKey];
    }
    
    /**
     * Get path for including PHP files (models, controllers, etc.)
     * 
     * @param string $type Type of include (model, controller, vendor)
     * @param string $file Filename to include
     * @return string Full path to the file
     */
    public static function getIncludePath(string $type, string $file): string
    {
        $root = self::getProjectRoot();
        
        $paths = [
            'model' => ['src/models', 'models'],
            'controller' => ['src/controllers', 'controllers'],
            'vendor' => ['vendor'],
            'config' => ['config'],
            'src' => ['src']
        ];
        
        $searchPaths = $paths[$type] ?? [$type];
        
        foreach ($searchPaths as $dir) {
            $fullPath = $root . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file;
            if (@file_exists($fullPath)) {
                return $fullPath;
            }
        }
        
        // Return best guess
        return $root . DIRECTORY_SEPARATOR . $searchPaths[0] . DIRECTORY_SEPARATOR . $file;
    }
    
    /**
     * Ensure a directory exists and is writable
     * 
     * @param string $path Directory path
     * @return bool True if directory exists or was created
     */
    public static function ensureDirectoryExists(string $path): bool
    {
        if (@is_dir($path)) {
            return @is_writable($path);
        }
        
        // Try to create directory
        $oldUmask = umask(0);
        $result = @mkdir($path, 0755, true);
        umask($oldUmask);
        
        return $result && @is_writable($path);
    }
    
    /**
     * Check if FileRise is running on shared hosting
     * 
     * @return bool
     */
    public static function isSharedHosting(): bool
    {
        // Check for common shared hosting indicators
        $indicators = [
            // open_basedir is set
            ini_get('open_basedir') !== '',
            
            // Common shared hosting paths
            stripos(__DIR__, '/home/') !== false,
            stripos(__DIR__, '/public_html') !== false,
            stripos(__DIR__, '/httpdocs') !== false,
            stripos(__DIR__, '/www/') !== false,
            
            // Restricted functions
            !function_exists('exec'),
            !function_exists('system'),
            
            // Can't access parent of document root
            isset($_SERVER['DOCUMENT_ROOT']) && !@is_readable(dirname($_SERVER['DOCUMENT_ROOT']))
        ];
        
        // If any indicator is true, likely shared hosting
        return array_reduce($indicators, function($carry, $item) {
            return $carry || $item;
        }, false);
    }
    
    /**
     * Get a safe filename for security-sensitive directories
     * 
     * @param string $type Directory type
     * @return string Obfuscated directory name
     */
    public static function getSecureDirectoryName(string $type): string
    {
        if (!self::isSharedHosting()) {
            // Use standard names on regular hosting
            return self::DATA_DIRS[$type] ?? $type;
        }
        
        // Generate consistent but non-obvious names for shared hosting
        $salt = $_SERVER['HTTP_HOST'] ?? 'filerise';
        $hash = substr(md5($salt . $type), 0, 8);
        
        $prefixes = [
            'uploads' => 'data',
            'users' => 'auth',
            'metadata' => 'meta',
            'trash' => 'temp'
        ];
        
        $prefix = $prefixes[$type] ?? 'store';
        return ".{$prefix}_{$hash}";
    }
    
    // Private detection methods
    
    private static function detectFromEnvironment(): ?string
    {
        $envPath = getenv('FILERISE_ROOT');
        return $envPath ? realpath($envPath) : null;
    }
    
    private static function detectFromKnownFiles(): ?string
    {
        $knownFiles = ['composer.json', 'README.md', 'LICENSE'];
        $currentDir = __DIR__;
        
        // Search up to 5 levels up
        for ($i = 0; $i < 5; $i++) {
            foreach ($knownFiles as $file) {
                if (@file_exists($currentDir . DIRECTORY_SEPARATOR . $file)) {
                    return realpath($currentDir);
                }
            }
            $parent = dirname($currentDir);
            if ($parent === $currentDir || !@is_readable($parent)) {
                break;
            }
            $currentDir = $parent;
        }
        
        return null;
    }
    
    private static function detectFromCurrentLocation(): ?string
    {
        // Assuming we're in src/SharedHosting/
        $path = dirname(dirname(__DIR__));
        return @is_dir($path) ? realpath($path) : null;
    }
    
    private static function detectFromDocumentRoot(): ?string
    {
        if (!isset($_SERVER['DOCUMENT_ROOT'])) {
            return null;
        }
        
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
        
        // Check if we're in a subdirectory of doc root
        if (stripos(__DIR__, $docRoot) === 0) {
            // Look for project root between here and doc root
            $current = __DIR__;
            while (strlen($current) >= strlen($docRoot)) {
                if (self::isValidProjectRoot($current)) {
                    return $current;
                }
                $parent = dirname($current);
                if ($parent === $current) break;
                $current = $parent;
            }
        }
        
        // Check if doc root itself is the project
        if (self::isValidProjectRoot($docRoot)) {
            return $docRoot;
        }
        
        // Check common subdirectories
        $subdirs = ['filerise', 'app', 'application'];
        foreach ($subdirs as $subdir) {
            $path = $docRoot . DIRECTORY_SEPARATOR . $subdir;
            if (self::isValidProjectRoot($path)) {
                return realpath($path);
            }
        }
        
        return null;
    }
    
    private static function detectFromWorkingDirectory(): ?string
    {
        $cwd = getcwd();
        return $cwd && self::isValidProjectRoot($cwd) ? realpath($cwd) : null;
    }
    
    private static function isValidProjectRoot(string $path): bool
    {
        if (!@is_dir($path) || !@is_readable($path)) {
            return false;
        }
        
        // Look for FileRise indicators
        $indicators = [
            'config',
            'public',
            'src',
            'public/index.html',
            'config/config.php'
        ];
        
        foreach ($indicators as $indicator) {
            if (@file_exists($path . DIRECTORY_SEPARATOR . $indicator)) {
                return true;
            }
        }
        
        return false;
    }
    
    private static function isAccessibleDirectory(string $path): bool
    {
        return @is_dir($path) && @is_readable($path) && @is_writable($path);
    }
    
    /**
     * Clear the path cache (useful for testing)
     */
    public static function clearCache(): void
    {
        self::$cache = [];
        self::$projectRoot = null;
    }
} 