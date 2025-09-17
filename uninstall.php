<?php
/**
 * Uninstall Custom File Manager
 *
 * This file is called when the plugin is uninstalled via WordPress admin.
 * It removes all plugin files from the uploads directory and cleans up rewrite rules.
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove all files in the storage directory
$upload_dir = wp_upload_dir();
$storage_dir = trailingslashit($upload_dir['basedir']) . 'custom-html-pages/';

if (is_dir($storage_dir)) {
    $files = glob($storage_dir . '*'); // get all file names
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); // delete file
        }
    }
    rmdir($storage_dir); // remove the directory
}

// Flush rewrite rules to clean up custom routes
flush_rewrite_rules();
