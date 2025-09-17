# Custom File Manager

**Contributors:** [rupeshkmrshah]  
**Tags:** html, pages, editor, frontend, custom pages, admin menu, file manager  
**Requires at least:** 5.0  
**Tested up to:** 6.4  
**Requires PHP:** 7.0  
**Stable tag:** 1.2.0  
**License:** GPL-2.0+  
**License URI:** [https://www.gnu.org/licenses/gpl-2.0.txt](https://www.gnu.org/licenses/gpl-2.0.txt)

A powerful file-based HTML page creator and manager with frontend interface and WordPress admin dashboard integration. Create, edit, and manage custom HTML, PHP, and JavaScript files with a built-in file manager.

---

## Description

This plugin provides a powerful file-based interface for creating and managing custom HTML, PHP, and JavaScript files directly from the frontend or the WordPress admin dashboard. Files are stored in the WordPress uploads directory and served at custom URLs with proper content-type headers.

### Features:
- File-based storage in `wp-content/uploads/custom-html-pages/`
- Built-in file manager with admin dashboard integration
- Support for `.php`, `.html`, and `.js` file extensions
- Frontend editor for creating and editing files
- Admin dashboard with dedicated top-level menu
- File manager interface showing filename, size, and modification date
- Automatic migration from database to files on plugin activation
- User permissions management
- AJAX-powered interface
- Basic HTML sanitization with meta tag support
- Direct file editing with auto-load functionality

---

## Installation

1. Upload the plugin files to the `/wp-content/plugins/custom-html-pages/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. The plugin will automatically create the storage directory at `wp-content/uploads/custom-html-pages/` and migrate any existing database pages to files.
4. Use the shortcode `[html_page_editor]` on any page to display the frontend editor.
5. Access the plugin's admin interface via the **"Custom HTML Pages"** menu in the WordPress sidebar.
6. Use the **"All Pages"** submenu to view the file manager and manage existing files.
7. Use the **"Create/Edit Page"** submenu to create or edit files within the admin dashboard.

---

## Usage

1. Add the shortcode `[html_page_editor]` to any page or post to enable the frontend editor.
2. Logged-in users with appropriate permissions can create and edit files from the frontend or admin dashboard.
3. Choose file extension: `.php`, `.html`, or `.js` when creating new files.
4. Files are stored in `wp-content/uploads/custom-html-pages/` and served at `yoursite.com/custom-page/filename`.
5. Use the admin file manager to view all files with details like size and modification date.
6. Click "Edit" in the file manager to directly edit any file with auto-loading functionality.
7. Files support proper content-type headers based on their extension.

---

## Frequently Asked Questions

### Who can use this plugin?
Users must be logged in and have the `create_html_pages` capability. By default, this is granted to subscribers and administrators.

### What file types are supported?
The plugin supports `.php`, `.html`, and `.js` file extensions. Files are served with appropriate content-type headers.

### Where are files stored?
Files are stored in the WordPress uploads directory at `wp-content/uploads/custom-html-pages/`. This directory is created automatically when the plugin is activated.

### How does the migration work?
When you activate the plugin, it automatically checks for existing database pages and migrates them to files in the uploads directory.

### Is the HTML sanitized?
Yes, basic HTML sanitization is applied using WordPress’s `wp_kses()` function. Meta tags are allowed for redirects and other functionality.

---

## Changelog

### 1.2.0
- Migrated from database storage to file-based storage in `wp-content/uploads/custom-html-pages/`
- Added built-in file manager interface in admin dashboard
- Support for `.php`, `.html`, and `.js` file extensions with proper content-type headers
- Automatic migration of existing database pages to files on plugin activation
- Enhanced admin interface with file details (filename, size, modification date)
- Direct file editing with auto-load functionality from file manager
- Improved frontend editor with file extension selection
- Fixed access issues with storage directory property

### 1.1.0
- Added WordPress admin dashboard integration with a dedicated top-level menu
- Added separate submenu pages for "All Pages" and "Create/Edit Page"
- Enqueued editor scripts and styles in admin for the editor submenu page
- Maintained existing frontend editor functionality
- Improved user experience by providing editor access in both frontend and admin

### 1.0.0
- Initial release
- Frontend editor
- Full page and injection support
- Admin management

---

## Upgrade Notice

### 1.2.0
Major update: Migrated to file-based storage with built-in file manager. Existing database pages will be automatically migrated to files.

### 1.1.0
Added admin dashboard integration with separate menu and editor page.

### 1.0.0
Initial release.

---

## Screenshots

1. Frontend editor interface with file extension selection  
2. Admin file manager showing files with size and modification details  
3. Admin editor form with auto-load functionality  
4. File creation with extension support (`.php`, `.html`, `.js`)

---

## License

This plugin is licensed under the [GPL-2.0+ License](https://www.gnu.org/licenses/gpl-2.0.txt).
