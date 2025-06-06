# FileRise Shared Hosting Testing Environment

This directory contains tools and scripts to simulate shared hosting restrictions locally for development and testing of the FileRise shared hosting fork.

## Quick Start

```bash
# Run with moderate restrictions (recommended for development)
./test-shared-hosting.sh

# Run with strict restrictions (simulates very restrictive hosts)
./test-shared-hosting.sh --strict

# Test specific hosting scenario
./test-shared-hosting.sh --scenario=cpanel
```

## What's Included

### Configuration Files
- `php-shared-hosting.ini` - Strict PHP configuration mimicking restrictive shared hosting
- `php-shared-hosting-moderate.ini` - Moderate restrictions for development

### Test Scripts
- `check-environment.php` - Comprehensive environment checker
- `test-restrictions.php` - Tests specific operations that might fail
- `test-shared-hosting.sh` - Launcher script with various options

### Test Scenarios
- `scenario1-public_html/` - cPanel-style structure
- `scenario2-httpdocs/` - Plesk-style structure
- `scenario3-www/` - Generic web hosting structure
- `scenario4-subfolder/` - Installation in a subdirectory

## Testing Different Restrictions

### 1. Basic Testing (Moderate Restrictions)
```bash
./test-shared-hosting.sh
```
This uses moderate restrictions that still allow file operations but simulate:
- Limited memory (256MB)
- No shell access (exec, system disabled)
- open_basedir restrictions
- Typical shared hosting PHP limits

### 2. Strict Testing
```bash
./test-shared-hosting.sh --strict
```
Simulates very restrictive environments where even file functions might be disabled:
- Very limited memory (128MB)
- Most file functions disabled
- Strict execution time limits
- Minimal PHP functionality

### 3. Scenario Testing
Test different directory structures:
```bash
# cPanel structure (public_html)
./test-shared-hosting.sh --scenario=cpanel

# Plesk structure (httpdocs)
./test-shared-hosting.sh --scenario=plesk

# Generic structure (www)
./test-shared-hosting.sh --scenario=generic

# Subdirectory installation
./test-shared-hosting.sh --scenario=subfolder
```

## Manual Testing Commands

### Test with open_basedir Only
```bash
php -d open_basedir=/path/to/project:/tmp -S localhost:8000
```

### Test with Custom Config
```bash
php -c testing/php-shared-hosting-moderate.ini -S localhost:8000
```

### Test with Minimal Extensions
```bash
php -n -d extension=json -d extension=curl -d extension=mbstring -S localhost:8000
```

## What to Test

### 1. Environment Check
Visit: `http://localhost:8000/check-environment.php`

This shows:
- PHP version and configuration
- Memory and execution limits
- Disabled functions
- Path restrictions
- Available extensions
- File operation capabilities

### 2. Restriction Tests
Visit: `http://localhost:8000/test-restrictions.php`

This tests:
- Parent directory access
- Cross-directory operations
- Path resolution methods
- Temp directory access
- Session handling
- Memory allocation

## Common Shared Hosting Configurations

### Netcup (webhosting)
- PHP 8.0+ available
- open_basedir enforced
- Limited to public_html
- exec/shell functions disabled

### 1&1 IONOS
- Multiple PHP versions
- Moderate restrictions
- .htaccess support
- Some shell functions available

### Strato
- Strict open_basedir
- Limited memory (256MB typical)
- No shell access
- Custom php.ini allowed in some plans

## Interpreting Results

### Green Flags ✅
- Can read/write within current directory
- Sessions work
- Required extensions loaded
- Temp directory accessible

### Yellow Flags ⚠️
- Cannot access parent directory
- Limited memory available
- Some functions disabled
- Restricted to document root

### Red Flags 🚫
- File operations disabled
- Cannot create directories
- No temp directory access
- Critical extensions missing

## Development Workflow

1. **Start Development**
   ```bash
   ./test-shared-hosting.sh --moderate
   ```

2. **Test Your Changes**
   - Make code changes
   - Refresh browser to test
   - Check error logs

3. **Verify Compatibility**
   ```bash
   ./test-shared-hosting.sh --strict
   ```

4. **Test Different Scenarios**
   ```bash
   for scenario in cpanel plesk generic subfolder; do
     echo "Testing $scenario..."
     ./test-shared-hosting.sh --scenario=$scenario
     # Test your code
   done
   ```

## Troubleshooting

### Server Won't Start
- Check if port 8000 is already in use
- Try a different port: `--port=8080`
- Ensure PHP is installed and in PATH

### Permission Errors
- The test creates directories - ensure you have write permissions
- Check open_basedir settings in output

### Function Not Available
- Switch from strict to moderate mode for development
- Check which functions FileRise actually needs

## Best Practices

1. **Always Test Both Modes**
   - Develop with moderate restrictions
   - Verify with strict restrictions

2. **Test Path Operations**
   - Ensure all paths are relative
   - Never assume parent directory access
   - Use the path resolver for all file operations

3. **Handle Errors Gracefully**
   - Check if functions exist before using
   - Provide fallbacks for restricted operations
   - Show helpful error messages

4. **Document Restrictions**
   - Note which functions are required
   - Document minimum PHP configuration
   - List required extensions

## Adding New Tests

To add new test cases:

1. Edit `test-restrictions.php` to add specific tests
2. Update `check-environment.php` for new checks
3. Add new scenarios in `test-shared-hosting.sh`

## Continuous Testing

For continuous testing during development:
```bash
# Install nodemon (if available)
npm install -g nodemon

# Watch for changes and restart
nodemon --exec "./test-shared-hosting.sh" --watch . --ext php
```

## Resources

- [PHP open_basedir documentation](https://www.php.net/manual/en/ini.core.php#ini.open-basedir)
- [Common PHP disable_functions](https://github.com/php/php-src)
- [Shared Hosting Limitations Guide](https://www.php.net/manual/en/features.safe-mode.php) 