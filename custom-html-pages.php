<?php
/**
 * Plugin Name: Custom File Manager
 * Plugin URI: https://github.com/rupeshkmrshah
 * Description: Allows users to create and edit custom HTML pages with frontend interface and admin dashboard integration
 * Version: 1.2.0
 * Author: Rupesh Shah
 * Author URI: https://github.com/rupeshkmrshah
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: custom-file-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Requires WP: 5.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CustomHtmlPagesPlugin {

    public $storage_dir;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->storage_dir = trailingslashit($upload_dir['basedir']) . 'custom-html-pages/';

        // Hook into WordPress
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_save_html_page', array($this, 'save_html_page'));
        add_action('wp_ajax_nopriv_save_html_page', array($this, 'save_html_page'));
        add_action('wp_ajax_load_html_page', array($this, 'load_html_page'));
        add_action('wp_ajax_nopriv_load_html_page', array($this, 'load_html_page'));
        add_action('wp_ajax_delete_html_page', array($this, 'delete_html_page'));
        add_action('wp_ajax_nopriv_delete_html_page', array($this, 'delete_html_page'));
        add_action('wp_ajax_get_user_pages', array($this, 'get_user_pages'));
        add_action('wp_ajax_nopriv_get_user_pages', array($this, 'get_user_pages'));
        add_action('template_redirect', array($this, 'serve_custom_page'));

        // Activation hook
        register_activation_hook(__FILE__, array($this, 'plugin_activate'));

        // Add shortcode for frontend interface
        add_shortcode('html_page_editor', array($this, 'render_frontend_interface'));

        // Add admin enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add custom rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }

    public function init() {
        // Allow subscribers to use this functionality
        $role = get_role('subscriber');
        if ($role) {
            $role->add_cap('create_html_pages');
        }

        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('create_html_pages');
        }

        // Ensure storage directory exists
        if (!file_exists($this->storage_dir)) {
            wp_mkdir_p($this->storage_dir);
        }
    }

    public function plugin_activate() {
        // Create storage directory on activation
        if (!file_exists($this->storage_dir)) {
            wp_mkdir_p($this->storage_dir);
        }
        // Migrate existing DB pages to files
        $this->migrate_db_pages_to_files();
    }

    public function migrate_db_pages_to_files() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'custom_html_pages';

        // Check if table exists
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $table_name
        ));

        if (!$table_exists) {
            return; // No table, no migration needed
        }

        $pages = $wpdb->get_results("SELECT * FROM {$table_name}");

        foreach ($pages as $page) {
            $filename = $this->sanitize_slug($page->page_slug) . '.php';
            $filepath = $this->storage_dir . $filename;

            if (!file_exists($filepath)) {
                file_put_contents($filepath, $page->html_content);
            }
        }

        // Optionally, drop the table after migration
        // $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
    }

    private function sanitize_slug($slug) {
        // Sanitize slug to prevent directory traversal and invalid filenames
        $slug = sanitize_title($slug);
        $slug = preg_replace('/[^a-z0-9\-_]/', '', $slug);
        return $slug;
    }

    public function add_rewrite_rules() {
        add_rewrite_rule('^custom-page/([^/]+)/?$', 'index.php?custom_html_page=$matches[1]', 'top');
    }

    public function add_query_vars($vars) {
        $vars[] = 'custom_html_page';
        return $vars;
    }

    public function serve_custom_page() {
        $page_slug = get_query_var('custom_html_page');

        if (!empty($page_slug)) {
            $filepath = $this->storage_dir . $page_slug;

            if (file_exists($filepath)) {
                // Determine content type based on extension
                $ext = pathinfo($filepath, PATHINFO_EXTENSION);
                $content_type = 'text/html';
                if ($ext === 'js') {
                    $content_type = 'application/javascript';
                } elseif ($ext === 'css') {
                    $content_type = 'text/css';
                }

                // Set proper headers
                header('Content-Type: ' . $content_type . '; charset=UTF-8');

                // Output the file content
                readfile($filepath);
                exit;
            } else {
                // Page not found
                wp_die('Page not found', '404 Not Found', array('response' => 404));
            }
        }
    }

    public function enqueue_scripts() {
        if (is_user_logged_in() && current_user_can('create_html_pages')) {
            wp_enqueue_script('html-page-editor', plugin_dir_url(__FILE__) . 'assets/js/html-page-editor.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('html-page-editor', plugin_dir_url(__FILE__) . 'assets/css/html-page-editor.css', array(), '1.0.0');

            wp_localize_script('html-page-editor', 'htmlPageEditor', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('html_page_nonce'),
                'site_url' => home_url()
            ));
        }
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook === 'custom-html-pages_page_custom-html-pages-editor') {
            wp_enqueue_script('html-page-editor', plugin_dir_url(__FILE__) . 'assets/js/html-page-editor.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('html-page-editor', plugin_dir_url(__FILE__) . 'assets/css/html-page-editor.css', array(), '1.0.0');

            wp_localize_script('html-page-editor', 'htmlPageEditor', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('html_page_nonce'),
                'site_url' => home_url()
            ));
        }
    }

    public function render_frontend_interface() {
        if (!is_user_logged_in() || !current_user_can('create_html_pages')) {
            return '<p>You need to be logged in with proper permissions to use this feature.</p>';
        }

        ob_start();
        ?>
        <div id="html-page-editor-container">
            <div class="editor-header">
                <h3>HTML Page Creator & Editor</h3>
                <div class="editor-controls">
                    <input type="text" id="page-slug" placeholder="page-slug" />
                    <select id="file-extension">
                        <option value=".php">.php</option>
                        <option value=".html">.html</option>
                        <option value=".js">.js</option>
                    </select>
                    <input type="text" id="page-title" placeholder="Page Title" />
                    <button id="load-page">Load Page</button>
                    <button id="save-page">Save Page</button>
                    <button id="delete-page">Delete Page</button>
                    <button id="preview-page">Preview</button>
                </div>
            </div>

            <div class="editor-content">
                <textarea id="html-content" rows="20" cols="80" placeholder="Enter your HTML content here..."></textarea>
            </div>

            <div class="page-list">
                <h4>Your Pages:</h4>
                <ul id="user-pages-list"></ul>
            </div>

            <div id="editor-messages"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function save_html_page() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'html_page_nonce')) {
            wp_die('Security check failed');
        }

        // Check user permissions
        if (!current_user_can('create_html_pages')) {
            wp_send_json_error('Insufficient permissions');
        }

        $page_slug = sanitize_title($_POST['page_slug']);
        $file_extension = sanitize_text_field($_POST['file_extension']);
        $page_title = sanitize_text_field($_POST['page_title']);

        // Validate file extension
        $allowed_extensions = array('.php', '.html', '.js');
        if (!in_array($file_extension, $allowed_extensions)) {
            wp_send_json_error('Invalid file extension');
        }

        // Allow meta tags for redirects, etc.
        $allowed_html = wp_kses_allowed_html('post');
        $allowed_html['meta'] = array(
            'charset' => array(),
            'content' => array(),
            'http-equiv' => array(),
            'name' => array(),
        );
        $html_content = wp_kses($_POST['html_content'], $allowed_html);

        if (empty($page_slug) || empty($page_title) || empty($html_content)) {
            wp_send_json_error('All fields are required');
        }

        $sanitized_slug = $this->sanitize_slug($page_slug);
        $filename = $sanitized_slug . $file_extension;
        $filepath = $this->storage_dir . $filename;

        // Check if file exists
        if (file_exists($filepath)) {
            // Check if user can edit (for now, allow if logged in, or add ownership check later)
            if (current_user_can('manage_options') || current_user_can('create_html_pages')) {
                $result = file_put_contents($filepath, $html_content);
                if ($result !== false) {
                    wp_send_json_success('Page updated successfully');
                } else {
                    wp_send_json_error('Failed to update page');
                }
            } else {
                wp_send_json_error('You can only edit your own pages');
            }
        } else {
            // Create new file
            $result = file_put_contents($filepath, $html_content);
            if ($result !== false) {
                // Flush rewrite rules to ensure the new page is accessible
                flush_rewrite_rules();
                wp_send_json_success('Page created successfully');
            } else {
                wp_send_json_error('Failed to create page');
            }
        }
    }

    public function load_html_page() {
        if (!wp_verify_nonce($_POST['nonce'], 'html_page_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('create_html_pages')) {
            wp_send_json_error('Insufficient permissions');
        }

        $page_slug = sanitize_text_field($_POST['page_slug']);
        $filepath = $this->storage_dir . $page_slug;

        if (file_exists($filepath)) {
            $html_content = file_get_contents($filepath);
            wp_send_json_success(array(
                'page_title' => pathinfo($page_slug, PATHINFO_FILENAME),
                'html_content' => $html_content,
                'created_by' => null,
                'can_edit' => current_user_can('create_html_pages')
            ));
        } else {
            wp_send_json_error('Page not found');
        }
    }

    public function delete_html_page() {
        if (!wp_verify_nonce($_POST['nonce'], 'html_page_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('create_html_pages')) {
            wp_send_json_error('Insufficient permissions');
        }

        $page_slug = sanitize_text_field($_POST['page_slug']);
        $filepath = $this->storage_dir . $page_slug;

        if (!file_exists($filepath)) {
            wp_send_json_error('Page not found');
        }

        // Check permissions (for now, allow if can create)
        if (!current_user_can('create_html_pages')) {
            wp_send_json_error('You can only delete your own pages');
        }

        if (unlink($filepath)) {
            flush_rewrite_rules();
            wp_send_json_success('Page deleted successfully');
        } else {
            wp_send_json_error('Failed to delete page');
        }
    }

    public function get_user_pages() {
        if (!wp_verify_nonce($_POST['nonce'], 'html_page_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('create_html_pages')) {
            wp_send_json_error('Insufficient permissions');
        }

        $files = scandir($this->storage_dir);
        $pages = array();

        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_file($this->storage_dir . $file)) {
                $filepath = $this->storage_dir . $file;
                $pages[] = array(
                    'page_slug' => $file,
                    'page_title' => pathinfo($file, PATHINFO_FILENAME),
                    'created_at' => date('Y-m-d H:i:s', filemtime($filepath)),
                    'page_url' => home_url('/custom-page/' . $file)
                );
            }
        }

        // Sort by modified time desc
        usort($pages, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        wp_send_json_success($pages);
    }
}

// Initialize the plugin
new CustomHtmlPagesPlugin();

// Add admin menu for managing pages
add_action('admin_menu', 'html_pages_admin_menu');
function html_pages_admin_menu() {
    add_menu_page(
        'Custom HTML Pages',
        'Custom HTML Pages',
        'manage_options',
        'custom-html-pages',
        'html_pages_admin_page',
        'dashicons-editor-code'
    );

    add_submenu_page(
        'custom-html-pages',
        'All Pages',
        'All Pages',
        'manage_options',
        'custom-html-pages',
        'html_pages_admin_page'
    );

    add_submenu_page(
        'custom-html-pages',
        'Create/Edit Page',
        'Create/Edit Page',
        'manage_options',
        'custom-html-pages-editor',
        'html_pages_editor_page'
    );
}

function html_pages_admin_page() {
    // Handle delete action
    if (isset($_POST['action']) && $_POST['action'] === 'delete_html_page_admin') {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_page_admin')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $page_slug = sanitize_text_field($_POST['page_slug']);
        $plugin = new CustomHtmlPagesPlugin();
        $filepath = $plugin->storage_dir . $page_slug;

        if (file_exists($filepath)) {
            if (unlink($filepath)) {
                flush_rewrite_rules();
                echo '<div class="notice notice-success"><p>File deleted successfully.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to delete file.</p></div>';
            }
        } else {
            echo '<div class="notice notice-error"><p>File not found.</p></div>';
        }
    }

    $plugin = new CustomHtmlPagesPlugin();
    $files = scandir($plugin->storage_dir);
    $pages = array();

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..' && is_file($plugin->storage_dir . $file)) {
            $filepath = $plugin->storage_dir . $file;
            $pages[] = array(
                'filename' => $file,
                'size' => filesize($filepath),
                'modified' => filemtime($filepath)
            );
        }
    }

    // Sort by modified time desc
    usort($pages, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });

    echo '<div class="wrap">';
    echo '<h1>Custom HTML Pages - File Manager</h1>';
    echo '<p>Use the shortcode <code>[html_page_editor]</code> on any page to display the frontend editor. Or use the "Create/Edit Page" submenu to manage files.</p>';

    if ($pages) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Filename</th><th>Size</th><th>Modified</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        foreach ($pages as $page) {
            echo '<tr>';
            echo '<td>' . esc_html($page['filename']) . '</td>';
            echo '<td>' . size_format($page['size']) . '</td>';
            echo '<td>' . date('Y-m-d H:i:s', $page['modified']) . '</td>';
            echo '<td>';
            echo '<a href="' . home_url('/custom-page/' . $page['filename']) . '" target="_blank">View</a>';
            echo ' | ';
            echo '<a href="' . admin_url('admin.php?page=custom-html-pages-editor&load=' . urlencode($page['filename'])) . '">Edit</a>';
            echo ' | ';
            echo '<form method="post" style="display:inline;">';
            wp_nonce_field('delete_page_admin');
            echo '<input type="hidden" name="page_slug" value="' . esc_attr($page['filename']) . '">';
            echo '<input type="hidden" name="action" value="delete_html_page_admin">';
            echo '<button type="submit" onclick="return confirm(\'Are you sure you want to delete this file?\')">Delete</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No files created yet.</p>';
    }
    echo '</div>';
}

function html_pages_editor_page() {
    echo '<div class="wrap">';
    echo '<h1>Create/Edit HTML Page</h1>';
    echo '<p>Use this form to create or edit custom HTML pages.</p>';

    // Output the editor form HTML (similar to render_frontend_interface but without permission check)
    ?>
    <div id="html-page-editor-container">
        <div class="editor-header">
            <h3>HTML Page Creator & Editor</h3>
            <div class="editor-controls">
                <input type="text" id="page-slug" placeholder="page-slug" />
                <select id="file-extension">
                    <option value=".php">.php</option>
                    <option value=".html">.html</option>
                    <option value=".js">.js</option>
                </select>
                <input type="text" id="page-title" placeholder="Page Title" />
                <button id="load-page">Load Page</button>
                <button id="save-page">Save Page</button>
                <button id="delete-page">Delete Page</button>
                <button id="preview-page">Preview</button>
            </div>
        </div>

        <div class="editor-content">
            <textarea id="html-content" rows="20" cols="80" placeholder="Enter your HTML content here..."></textarea>
        </div>

        <div class="page-list">
            <h4>Your Pages:</h4>
            <ul id="user-pages-list"></ul>
        </div>

        <div id="editor-messages"></div>
    </div>
    <?php
    echo '</div>';
    echo '<div style="margin-top: 40px; text-align: center; color: #999; font-size: 13px;">
        &copy; ' . date('Y') . ' <a href="https://github.com/rupeshkmrshah" target="_blank">Rupesh Shah</a>. All rights reserved.
      </div>';
}
?>
