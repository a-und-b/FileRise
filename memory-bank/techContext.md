# FileRise Shared Hosting Fork - Technical Context

## Technology Stack

### Core Technologies
- **Language**: PHP 8.1+ (maintaining compatibility with original)
- **Database**: None - file-based storage system
- **Web Server**: Apache (primary), Nginx (supported)
- **Architecture**: MVC-style with controllers and models

### Frontend Stack
- **CSS Framework**: Bootstrap 4.5.2
- **Icons**: Google Material Icons
- **Code Editor**: CodeMirror 5.65.5
- **File Upload**: Resumable.js 1.1.0
- **Search**: Fuse.js 6.6.2 (fuzzy search)
- **Sanitization**: DOMPurify 2.4.0

### PHP Dependencies (Composer)
```json
{
  "jumbojett/openid-connect-php": "^1.0.0",
  "phpseclib/phpseclib": "~3.0.7",
  "robthree/twofactorauth": "^3.0",
  "endroid/qr-code": "^5.0",
  "sabre/dav": "^4.4"
}
```

## Shared Hosting Constraints

### PHP Configuration Limitations
- **open_basedir**: Restricts file access to specific directories
- **disable_functions**: Some functions may be disabled
- **memory_limit**: Often limited to 128MB-256MB
- **max_execution_time**: Usually 30-60 seconds
- **upload_max_filesize**: Typically 32MB-128MB

### Directory Structure Constraints
```
Typical Shared Hosting:
/home/username/
├── public_html/        <- Document root (varies)
├── .htaccess          <- User-configurable
├── logs/              <- Often inaccessible
└── tmp/               <- May have restrictions

FileRise Must Adapt To:
- public_html/
- httpdocs/
- www/
- html/
- web/
```

### Permission Constraints
- Cannot create files outside document root
- Cannot modify PHP configuration
- Limited to 755/644 permissions typically
- No shell access for installation scripts

## Development Environment Setup

### Local Testing for Shared Hosting
```bash
# Simulate open_basedir locally
php -d open_basedir=/path/to/project -S localhost:8000

# Test with minimal PHP extensions
php -n -d extension=json -d extension=curl -S localhost:8000
```

### Required PHP Extensions
- **json** (core)
- **curl** (for OAuth/WebDAV)
- **zip** (for archive operations)
- **mbstring** (for i18n)
- **openssl** (for encryption)

## Key Technical Challenges

### 1. Path Resolution
```php
// Current approach (fails on shared hosting)
define('ROOT_DIR', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_DIR . '/uploads');

// Shared hosting approach needed
define('ROOT_DIR', detectRootDirectory());
define('UPLOAD_DIR', ROOT_DIR . '/uploads');
```

### 2. File Storage Location
- Must store within document root
- Need fallback locations for restricted environments
- Maintain security while being flexible

### 3. Configuration Management
```php
// Dynamic configuration based on environment
class Config {
    private static $instance = null;
    private $settings = [];
    
    public function __construct() {
        $this->detectEnvironment();
        $this->loadDefaults();
        $this->applyEnvironmentOverrides();
    }
}
```

### 4. WebDAV Compatibility
- May not work on all shared hosting
- Needs graceful degradation
- Alternative access methods required

## Security Considerations

### Shared Hosting Security
- Cannot rely on .htaccess in all directories
- Must protect sensitive files programmatically
- Need robust session management
- Extra validation for file operations

### Data Protection
```
/uploads/           <- User files
/users/             <- User credentials (bcrypt)
/metadata/          <- File metadata, tags
/.security/         <- Security tokens, keys
```

## Testing Requirements

### Environment Matrix
| Feature | Shared Hosting | VPS | Self-Hosted |
|---------|---------------|-----|-------------|
| Basic Upload | ✅ | ✅ | ✅ |
| Large Files | ⚠️ | ✅ | ✅ |
| WebDAV | ❓ | ✅ | ✅ |
| OAuth/SSO | ✅ | ✅ | ✅ |
| 2FA | ✅ | ✅ | ✅ |

### Test Hosting Providers
1. **Netcup** - Popular German provider
2. **1&1 IONOS** - Large European provider
3. **Strato** - German hosting
4. **DreamHost** - US provider
5. **SiteGround** - International

## Performance Considerations

### Shared Hosting Optimization
- Implement pagination (already present)
- Cache file listings where possible
- Minimize file system calls
- Optimize search indexing
- Lazy load images/previews

### Resource Usage
- Target: < 128MB memory per request
- File operations in chunks
- Background processing where available
- Efficient session management 