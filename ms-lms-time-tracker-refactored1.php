
<?php
/*
Plugin Name: Time Tracker - Masterstudy LMS 
Description: Tracks the time students spend on courses and lessons in MasterStudy LMS.
Version: 1.1
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

// --- 1. Add Courses View on Main Admin Page ---
function mstimer_courses_view() {
    // This function renders the courses view in the admin page.
    echo '<h2>Courses Overview</h2>';
    // Display courses list
    // This would need to pull course data from LMS (using a hypothetical get_courses() function for now).
    $courses = get_courses();  // Replace with real data call

    echo '<ul>';
    foreach ($courses as $course) {
        echo '<li>' . esc_html($course->post_title) . '</li>';
    }
    echo '</ul>';
}

add_action('admin_menu', 'mstimer_add_menu_pages');
function mstimer_add_menu_pages() {
    // Add the new Courses Overview page in the main admin menu.
    add_menu_page('Courses', 'Courses Overview', 'manage_options', 'mstimer-courses', 'mstimer_courses_view', 'dashicons-welcome-learn-more', 6);
}

// --- 2. Convert Time Format (Seconds -> Hours:Minutes:Seconds) ---
function format_time_human_readable($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $seconds = $seconds % 60;
    return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
}

// Modify the time display where necessary (in lesson or student views):
// echo format_time_human_readable($time_spent_in_seconds);

// --- 3. Add Navigation Buttons (General Course View and Admin Page) ---
function mstimer_add_back_buttons() {
    // This function adds a "Back" button to navigate between pages
    echo '<a href="admin.php?page=mstimer-courses" class="button button-primary">Back to Courses View</a>';
    echo '<a href="admin.php?page=admin" class="button button-secondary">Back to Admin Page</a>';
}

// Call the button function on lesson and student detail views
// mstimer_add_back_buttons();

// --- 4. Fix Export URL Functionality ---
function mstimer_export_url() {
    // Dynamically set the export URL based on the current page or tab
    $current_url = get_current_url();  // Assuming the current URL is retrieved here
    $export_url = $current_url . '&export=1';  // Example of appending export param to current URL
    return $export_url;
}

// --- 5. Organize Code ---
// Include other files for better organization (these files need to be created)
include_once(plugin_dir_path(__FILE__) . 'includes/admin-pages.php');
include_once(plugin_dir_path(__FILE__) . 'includes/time-formatting.php');
include_once(plugin_dir_path(__FILE__) . 'includes/navigation.php');

/* End of main plugin file */
