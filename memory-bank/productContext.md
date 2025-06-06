# FileRise Shared Hosting Fork - Product Context

## Why This Fork Exists

The original FileRise is an excellent self-hosted file management solution, but it assumes users have:
- Root or administrative access to their server
- Ability to modify server configurations
- Control over PHP settings and directory structures
- A VPS or dedicated server environment

However, a large portion of web users rely on **shared hosting** for their websites due to:
- Lower costs compared to VPS/dedicated servers
- Managed environment (no server administration required)
- Built-in security and maintenance
- Simplified setup for non-technical users

## The Problem

When users try to install FileRise on shared hosting, they encounter:

### 1. **open_basedir Restrictions**
- PHP cannot access files outside designated directories
- Causes "Permission denied" errors
- Prevents FileRise from accessing parent directories

### 2. **Fixed Directory Structure**
- FileRise expects `/public/` to be the document root
- Shared hosting uses `/public_html/`, `/httpdocs/`, or `/www/`
- No way to change this without server access

### 3. **Setup Wizard Issues**
- Fails to detect if admin user needs to be created
- Confusing error messages about permissions
- Users get stuck in setup loops

## Target Users

### Primary Users
- **Small Business Owners**: Need file sharing without expensive hosting
- **Freelancers**: Want client file portals on budget hosting
- **Personal Users**: Family file sharing on existing web hosting
- **Students/Educators**: Limited budgets, existing shared hosting

### User Personas

1. **Sarah - Freelance Designer**
   - Has shared hosting for portfolio site
   - Wants to share large design files with clients
   - No technical server knowledge
   - Needs simple installation

2. **Mike - Small Business Owner**
   - Uses budget hosting for company website
   - Needs internal file sharing for team
   - Cannot afford dedicated server
   - Values security and ease of use

3. **Lisa - Family Organizer**
   - Has hosting for family blog
   - Wants to share photos/videos with relatives
   - Non-technical user
   - Needs straightforward setup

## How This Fork Solves the Problems

### 1. **Intelligent Path Detection**
- Automatically detects hosting environment
- Works with any document root structure
- No manual configuration needed

### 2. **open_basedir Compatibility**
- All operations stay within allowed directories
- Smart fallback mechanisms
- Clear error messages if restrictions apply

### 3. **Enhanced Setup Experience**
- Improved setup wizard with better detection
- Clear guidance for shared hosting users
- Automatic permission checking and reporting

### 4. **Zero Configuration Goal**
- Upload files via FTP
- Navigate to URL
- Complete setup wizard
- Start using immediately

## Value Proposition

**"FileRise for Everyone"** - Making powerful file management accessible to users regardless of their hosting environment. No server expertise required, no expensive hosting needed - just upload and go.

## Feature Preservation

While adapting for shared hosting, we maintain:
- ✅ All file management features
- ✅ User authentication and permissions
- ✅ File sharing capabilities
- ✅ Tag system and search
- ✅ Multi-language support
- ✅ Responsive UI with dark/light modes
- ⚠️ WebDAV (where hosting allows)

## Success Metrics

1. **Installation Success Rate**: 95%+ on shared hosting
2. **Setup Time**: Under 5 minutes for non-technical users
3. **Feature Parity**: 100% core features working
4. **User Satisfaction**: Positive feedback from shared hosting users 