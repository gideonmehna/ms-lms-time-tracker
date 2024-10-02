<?php
/*
Plugin Name: Time Tracker - Masterstudy LMS 
Description: Tracks the time students spend on courses and lessons in MasterStudy LMS.
Version: 1.2
Author: Gideon Mehna
Author URI: https://elyownsoftware.com
Plugin URI: https://elyownsoftware.com/
Text Domain: ms-lms-time-tracker
Domain Path: /languages
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MSTIMER_VERSION', '1.2');
define('MSTIMER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MSTIMER_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once MSTIMER_PLUGIN_DIR . 'includes/admin/admin-menu.php';
require_once MSTIMER_PLUGIN_DIR . 'includes/admin/admin-pages.php';
require_once MSTIMER_PLUGIN_DIR . 'includes/admin/data-export.php';
require_once MSTIMER_PLUGIN_DIR . 'includes/database/database-functions.php';
require_once MSTIMER_PLUGIN_DIR . 'includes/frontend/enqueue-scripts.php';
require_once MSTIMER_PLUGIN_DIR . 'includes/frontend/ajax-handlers.php';
require_once MSTIMER_PLUGIN_DIR . 'includes/helpers/time-formatting.php';
require_once MSTIMER_PLUGIN_DIR . 'includes/helpers/url-helpers.php';

// Activation hook
register_activation_hook(__FILE__, 'mstimer_activate_plugin');

function mstimer_activate_plugin() {
    mstimer_create_tables();
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'mstimer_deactivate_plugin');

function mstimer_deactivate_plugin() {
    // Perform any cleanup tasks here
}

// Initialize the plugin
add_action('plugins_loaded', 'mstimer_init_plugin');

function mstimer_init_plugin() {
    // Initialize admin menus
    add_action('admin_menu', 'mstimer_add_admin_menu');

    // Initialize frontend scripts
    add_action('wp_enqueue_scripts', 'mstimer_enqueue_scripts');

    // Initialize AJAX handlers
    add_action('wp_ajax_save_student_time', 'mstimer_save_student_time');

    // Initialize admin scripts and styles
    add_action('admin_enqueue_scripts', 'mstimer_enqueue_admin_styles');

    // Initialize CSV export
    add_action('admin_init', 'mstimer_export_csv');
}

// Helper function to log errors
function mstimer_log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($message);
    }
}