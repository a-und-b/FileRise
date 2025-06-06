# FileRise Shared Hosting Fork - Project Brief

## Project Overview
This is a fork of the original FileRise project (a self-hosted web file manager) aimed at making it compatible with shared hosting environments. The original FileRise requires root access and specific server configurations that are not available on standard shared hosting platforms.

## Core Objective
Transform FileRise into a web application that runs "out of the box" on shared hosting environments without requiring manual path adjustments or server-level configurations.

## Key Requirements

### 1. Shared Hosting Compatibility
- **open_basedir Support**: Must work within PHP's open_basedir restrictions
- **Flexible Directory Structure**: Support various document root configurations (public_html/, httpdocs/, www/)
- **No Root Access Required**: All functionality must work with standard shared hosting permissions

### 2. Automatic Environment Detection
- Detect hosting environment type (shared/VPS/dedicated)
- Automatic path detection and configuration
- Intelligent fallback mechanisms for restricted environments

### 3. Backward Compatibility
- Existing FileRise installations must continue to work
- Provide migration path for users switching to new structure
- No breaking changes to core functionality

### 4. Target Environments
- **Primary**: Shared hosting providers (Netcup, 1&1, Strato, etc.)
- **Secondary**: VPS with restricted permissions
- **Maintained**: Standard self-hosted environments

## Success Criteria
1. ✅ FileRise runs on shared hosting without manual configuration
2. ✅ Setup wizard correctly detects and creates admin users
3. ✅ All file management features work within hosting restrictions
4. ✅ Existing installations remain functional after updates

## Implementation Priorities
1. **High**: Path detection and open_basedir compatibility
2. **Medium**: Setup wizard improvements
3. **Low**: Installer tool and advanced environment detection

## Technical Constraints
- PHP 8.0+ (matching original requirements)
- No external database (file-based storage)
- Must work with standard PHP extensions only
- WebDAV functionality should remain intact where possible 