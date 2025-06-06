# FileRise Shared Hosting Installation & Testing Guide

This guide provides step-by-step instructions for deploying and testing the shared-hosting-compatible version of FileRise on a live provider.

---

## 1. Prerequisites

Before you begin, ensure you have the following:

*   **Shared Hosting Account**: Access to a standard shared hosting account (e.g., from providers like Netcup, 1&1, HostGator, SiteGround).
*   **FTP/SFTP Client**: A tool like FileZilla, Cyberduck, or WinSCP to transfer files to your server.
*   **Hosting Credentials**: Your FTP/SFTP username, password, and server address.

---

## 2. Installation Steps

The new universal configuration makes installation straightforward.

### Step 2.1: Prepare the Files

1.  **Download/Clone**: Make sure you have the latest version of the project files on your local machine.
2.  **Cleanup (Optional but Recommended)**: Remove any development-specific files or directories that are not needed for production, such as the `.git` directory (if you cloned it) or local testing files.

### Step 2.2: Upload Files via FTP/SFTP

1.  **Connect to Your Server**: Open your FTP client and connect to your shared hosting account.
2.  **Navigate to the Web Root**: Go to your primary web-accessible directory. This is usually named `public_html`, `httpdocs`, `www`, or `htdocs`.
3.  **Upload Everything**: Transfer the entire FileRise project directory to this location.

    *Example Structure:*
    ```
    public_html/
    └── filerise/
        ├── config/
        ├── public/
        ├── src/
        ├── ... (all other files and folders)
    ```

    You can either upload the files into a subdirectory (like `/filerise/`) or directly into the web root.

### Step 2.3: No Manual Configuration Needed!

Thanks to the new bootstrap system, you **do not** need to manually edit any configuration files. The `PathResolver` will automatically detect the correct paths for your environment.

---

## 3. The Setup Wizard

1.  **Access the Installer**: Open your web browser and navigate to the URL where you uploaded FileRise.
    *   If you uploaded to a subdirectory: `http://yourdomain.com/filerise/`
    *   If you uploaded to the root: `http://yourdomain.com/`

2.  **Create Your Admin Account**: You will be greeted by the FileRise setup wizard. Follow the on-screen instructions to create your primary administrator account.

    *   The system automatically detects that no admin user exists and will guide you through creating one.
    *   This process should now work flawlessly, even on hosts with `open_basedir` restrictions.

---

## 4. Manual Testing Checklist

After setting up your admin account, log in and perform the following tests to ensure all core features are working correctly.

### ✅ File Operations
- [ ] **Upload a file**: Drag and drop a file or use the upload button.
- [ ] **Download a file**: Click on a file to download it.
- [ ] **Rename a file**: Select a file and choose the "Rename" option.
- [ ] **Move a file**: Drag a file into a different folder.
- [ ] **Copy a file**: Select a file and use the "Copy" functionality.
- [ ] **View a file**: Click on a text-based or image file to see if the viewer works.

### ✅ Folder Operations
- [ ] **Create a folder**: Use the "New Folder" button.
- [ ] **Rename a folder**: Select a folder and rename it.
- [ ] **Navigate into a folder**: Click to open a folder and verify the breadcrumb navigation.
- [ ] **Delete an empty folder**: Delete the folder you created.

### ✅ Sharing Functionality
- [ ] **Create a file share link**: Share a file with an optional password.
- [ ] **Access the share link**: Open the link in a private/incognito browser window and test password protection.
- [ ] **Create a folder share link**: Share a folder.
- [ ] **Access the folder share link**: Verify you can see the contents of the shared folder.

### ✅ Trash & Recovery
- [ ] **Delete a file**: Verify it moves to the "Trash" area.
- [ ] **Restore a file**: Go to the Trash view and restore the file to its original location.
- [ ] **Permanently delete a file**: Delete a file from the Trash and confirm it's gone.

### ✅ User & Session Management
- [ ] **Log out**: Click the logout button.
- [ ] **Log back in**: Ensure you can log in again successfully.
- [ ] **(Optional)**: Create a second, non-admin user and verify their restricted permissions (e.g., if you set them as read-only).

---

## 5. Troubleshooting

*   **500 Internal Server Error**: This usually indicates a file permission or PHP version issue.
    *   **File Permissions**: Ensure directories are set to `755` and files are set to `644`.
    *   **PHP Version**: Check your hosting control panel and make sure you are running PHP 7.4 or newer.
*   **"Cannot write to..." Errors**: If you see any errors about writing to a directory, double-check the folder permissions. The bootstrap process creates directories, but some hosts are more restrictive.
*   **Setup Wizard Doesn't Appear**: If you see a blank page or a FileRise login screen instead of the setup wizard, it may mean the `users/` directory or `users/users.txt` file already exists from a previous attempt. Delete the `users/` directory and try again. 