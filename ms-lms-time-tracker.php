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

// Function to get the current URL
function get_current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    return $url;
}

// Function to remove the lesson ID from the URL
function remove_lesson_id_from_url($url, $lesson_id) {
    return str_replace($lesson_id, '', $url);
}
/**
 * Enqueue scripts for frontend (time tracking)
 */
function mstimer_enqueue_scripts() {
    if (!is_page() && !is_single()) {
        wp_enqueue_script('mstimer-js', plugins_url('/assets/js/mstimer.js', __FILE__), array('jquery'), null, true);

        $course_id = null;
        $lesson_id = null;

    
        $lesson_id = get_the_ID();
    
        $current_url = get_current_url();

        // Remove the lesson ID from the URL
        $clean_url = remove_lesson_id_from_url($current_url, $lesson_id);

        // Get the post ID from the cleaned URL
        $course_id = url_to_postid($clean_url);
    
    
        wp_localize_script('mstimer-js', 'mstimer_vars', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'user_id' => get_current_user_id(),
            'course_id' => $course_id,
            'lesson_id' => $lesson_id,
        ));
    }     
}

add_action('wp_enqueue_scripts', 'mstimer_enqueue_scripts');


function mstimer_enqueue_admin_styles($hook) {
    if (strpos($hook, 'mstimer') !== false) {
        wp_enqueue_style('mstimer-admin-css', plugins_url('/assets/css/mstimer-admin.css', __FILE__));
    }
}
add_action('admin_enqueue_scripts', 'mstimer_enqueue_admin_styles');


/**
 * Helper function to log errors
 */
function mstimer_log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($message);
    }
}
/**
 * Add admin menu and pages
 */


/**
 * Create database tables on plugin activation
 */
function mstimer_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    $table_name = $wpdb->prefix . 'mstimer_study_sessions';
    $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
        `id` BIGINT(20) NOT NULL AUTO_INCREMENT,
        `user_id` BIGINT(20) NOT NULL,
        `course_id` BIGINT(20) NOT NULL,
        `lesson_id` BIGINT(20) NOT NULL,
        `start_time` DATETIME NOT NULL,
        `end_time` DATETIME NOT NULL,
        `session_date` DATE NOT NULL,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `course_id` (`course_id`),
        KEY `lesson_id` (`lesson_id`)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $result = dbDelta($sql);
    
    if (empty($result)) {
        error_log('mstimer_create_tables: dbDelta returned empty result');
    } else {
        error_log('mstimer_create_tables: dbDelta result: ' . print_r($result, true));
    }

    // Check if the table was actually created
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    if (!$table_exists) {
        error_log("mstimer_create_tables: Table $table_name was not created successfully");
    } else {
        error_log("mstimer_create_tables: Table $table_name was created successfully");
    }
}
register_activation_hook(__FILE__, 'mstimer_create_tables');

function mstimer_check_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        mstimer_create_tables();
        error_log("mstimer_check_table: Table $table_name did not exist, attempted to create it");
    }
}
add_action('init', 'mstimer_check_table');

add_action('admin_notices', 'remove_admin_notices_for_plugin_page', 1);

function remove_admin_notices_for_plugin_page() {
    // Check if the current page is one of your plugin's pages
    $current_screen = get_current_screen();
    
    $plugin_pages = array(
        'toplevel_page_mstimer_admin',
        'time-tracking_page_mstimer_courses',
        'admin_page_mstimer_course_detail',
        'admin_page_mstimer_lesson_detail',
        'admin_page_mstimer_student_detail',
        'time-tracking_page_mstimer_students'
    );

    if (in_array($current_screen->id, $plugin_pages)) {
        // Remove all admin notices
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    } 
    
}


/**
 * Add admin menu and pages
 */
function mstimer_add_admin_menu() {
    add_menu_page('Time Tracking', 'Time Tracking', 'manage_options', 'mstimer_admin', 'mstimer_admin_page');
    add_submenu_page('mstimer_admin', 'Courses', 'Courses', 'manage_options', 'mstimer_courses', 'mstimer_courses_page');
    add_submenu_page(null, 'Course Detail', 'Course Detail', 'manage_options', 'mstimer_course_detail', 'mstimer_course_detail_page');
    add_submenu_page(null, 'Lesson Detail', 'Lesson Detail', 'manage_options', 'mstimer_lesson_detail', 'mstimer_lesson_detail_page');
    add_submenu_page(null, 'Student Detail', 'Student Detail', 'manage_options', 'mstimer_student_detail', 'mstimer_student_detail_page');
    add_submenu_page('mstimer_admin', 'Students', 'Students', 'manage_options', 'mstimer_students', 'mstimer_students_page');
}

add_action('admin_menu', 'mstimer_add_admin_menu');

/**
 * Display admin page content
 */
function mstimer_admin_page() {
    echo '<h1>Time Tracking</h1>';
    echo '<p>Welcome to the Time Tracking plugin for Masterstudy LMS.</p>';
}

/**
 * Query functions for reusable logic
 */

// Fetch average time for courses within date range
function mstimer_get_course_data($start_date, $end_date) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    try {
        return $wpdb->get_results($wpdb->prepare("
            SELECT course_id, AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_time 
            FROM $table_name 
            WHERE start_time BETWEEN %s AND %s
            GROUP BY course_id
        ", $start_date, $end_date . ' 23:59:59'));
    } catch (Exception $e) {
        mstimer_log_error("Error fetching course data: " . $e->getMessage());
        return [];
    }
}
/**
 * Display courses page content
 */
function mstimer_courses_page() {
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

    echo '<h1>Courses</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="mstimer_courses">';
    echo 'Start Date: <input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
    echo 'End Date: <input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
    echo '<input type="submit" value="Filter" class="button">';
    echo '</form>';

    $courses_data = mstimer_get_course_data($start_date, $end_date);
    echo '<pre>';
    var_dump($courses_data);
    echo '</pre>';

    // After the filter form
    $export_url = add_query_arg([
        'export_csv' => '1',
        'page_type' => 'courses',
        'start_date' => $start_date,
        'end_date' => $end_date,
    ]);

    echo '<a href="' . esc_url($export_url) . '" class="button">Export CSV</a>';


    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Course</th><th>Average Time (seconds)</th></tr></thead><tbody>';

    foreach ($courses_data as $data) {
        $course_title = get_the_title($data->course_id);
        $course_link = admin_url('admin.php?page=mstimer_course_detail&course_id=' . $data->course_id . '&start_date=' . $start_date . '&end_date=' . $end_date);
        echo '<tr>';
        echo '<td><a href="' . esc_url($course_link) . '">' . esc_html($course_title) . 'Link' .'</a></td>';
        echo '<td>' . esc_html(round($data->avg_time, 2)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}


function mstimer_course_detail_page() {
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

    if ($course_id == 0) {
        echo '<p>Invalid course.</p>';
        return;
    }

    $course_title = get_the_title($course_id);
    echo '<h1>Course: ' . esc_html($course_title) . '</h1>';

    // Tabs for Lesson View and Student View
    $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'lesson_view';

    echo '<h2 class="nav-tab-wrapper">';
    echo '<a href="' . esc_url(add_query_arg(['tab' => 'lesson_view'])) . '" class="nav-tab ' . ($current_tab == 'lesson_view' ? 'nav-tab-active' : '') . '">Lesson View</a>';
    echo '<a href="' . esc_url(add_query_arg(['tab' => 'student_view'])) . '" class="nav-tab ' . ($current_tab == 'student_view' ? 'nav-tab-active' : '') . '">Student View</a>';
    echo '</h2>';

    // Date range filter
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="mstimer_course_detail">';
    echo '<input type="hidden" name="course_id" value="' . esc_attr($course_id) . '">';
    echo '<input type="hidden" name="tab" value="' . esc_attr($current_tab) . '">';
    echo 'Start Date: <input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
    echo 'End Date: <input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
    echo '<input type="submit" value="Filter" class="button">';
    echo '</form>';

    $export_url = add_query_arg([
        'export_csv' => '1',
        'page_type' => 'students',
        'course_id'  => $course_id, // The ID of the course the student is enrolled in
        'start_date' => $start_date,
        'end_date'   => $end_date,
    ]);
    echo '<a href="' . esc_url($export_url) . '" class="button">Export CSV</a>';
    

    if ($current_tab == 'lesson_view') {
        mstimer_lesson_view($course_id, $start_date, $end_date);
    } else {
        mstimer_student_view($course_id, $start_date, $end_date);
    }
}

function mstimer_get_lessons_data($course_id, $start_date, $end_date) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    try {
        return $wpdb->get_results($wpdb->prepare("
            SELECT lesson_id, AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_time
            FROM $table_name
            WHERE course_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY lesson_id
        ", $course_id, $start_date, $end_date . ' 23:59:59'));
    } catch (Exception $e) {
        mstimer_log_error("Error fetching lesson data: " . $e->getMessage());
        return [];
    }
}


function mstimer_lesson_view($course_id, $start_date, $end_date) {
    global $wpdb;

    $lessons_data = mstimer_get_lessons_data($course_id, $start_date, $end_date);

    echo '<h2>Lesson View</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Lesson</th><th>Average Time (seconds)</th></tr></thead><tbody>';

    foreach ($lessons_data as $data) {
        $lesson_title = get_the_title($data->lesson_id);
        $lesson_link = admin_url('admin.php?page=mstimer_lesson_detail&lesson_id=' . $data->lesson_id . '&course_id=' . $course_id . '&start_date=' . $start_date . '&end_date=' . $end_date);
        echo '<tr>';
        echo '<td><a href="' . esc_url($lesson_link) . '">' . esc_html($lesson_title) . '</a></td>';
        echo '<td>' . esc_html(round($data->avg_time, 2)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function mstimer_lesson_student_times($lesson_id, $start_date, $end_date) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    try {
        $students_data = $wpdb->get_results($wpdb->prepare("
            SELECT user_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time
            FROM $table_name
            WHERE lesson_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY user_id
        ", $lesson_id, $start_date, $end_date . ' 23:59:59'));
    } catch (Exception $e) {
        mstimer_log_error("Error fetching student times for lesson: " . $e->getMessage());
        return;
    }

    echo '<h2>Student Times for Lesson</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Student</th><th>Total Time (seconds)</th></tr></thead><tbody>';

    foreach ($students_data as $data) {
        $user_info = get_userdata($data->user_id);
        $student_name = $user_info ? $user_info->display_name : 'Unknown User';
        echo '<tr>';
        echo '<td>' . esc_html($student_name) . '</td>';
        echo '<td>' . esc_html(round($data->total_time, 2)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function mstimer_lesson_detail_page() {
    $lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

    if ($lesson_id == 0 || $course_id == 0) {
        echo '<p>Invalid lesson or course.</p>';
        return;
    }

    $lesson_title = get_the_title($lesson_id);
    echo '<h1>Lesson: ' . esc_html($lesson_title) . '</h1>';

    // Date range filter
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="mstimer_lesson_detail">';
    echo '<input type="hidden" name="lesson_id" value="' . esc_attr($lesson_id) . '">';
    echo '<input type="hidden" name="course_id" value="' . esc_attr($course_id) . '">';
    echo 'Start Date: <input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
    echo 'End Date: <input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
    echo '<input type="submit" value="Filter" class="button">';
    echo '</form>';

    $export_url = add_query_arg([
        'export_csv' => '1',
        'page_type'  => 'lessons',
        'course_id'  => $course_id,  // The course that contains the lesson
        'lesson_id'  => $lesson_id,  // The specific lesson being exported
        'start_date' => $start_date,
        'end_date'   => $end_date,
    ]);
    echo '<a href="' . esc_url($export_url) . '" class="button">Export CSV</a>';
    

    mstimer_lesson_student_times($lesson_id, $start_date, $end_date);
}

function mstimer_get_students_data($course_id, $start_date, $end_date) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    try {
        return $wpdb->get_results($wpdb->prepare("
            SELECT user_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time
            FROM $table_name
            WHERE course_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY user_id
        ", $course_id, $start_date, $end_date . ' 23:59:59'));
    } catch (Exception $e) {
        mstimer_log_error("Error fetching student data: " . $e->getMessage());
        return [];
    }
}

function mstimer_student_view($course_id, $start_date, $end_date) {
    global $wpdb;

    $students_data = mstimer_get_students_data($course_id, $start_date, $end_date);

    echo '<h2>Student View</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Student</th><th>Total Time (seconds)</th></tr></thead><tbody>';

    foreach ($students_data as $data) {
        $user_info = get_userdata($data->user_id);
        $student_name = $user_info ? $user_info->display_name : 'Unknown User';
        $student_link = admin_url('admin.php?page=mstimer_student_detail&user_id=' . $data->user_id . '&course_id=' . $course_id . '&start_date=' . $start_date . '&end_date=' . $end_date);
        echo '<tr>';
        echo '<td><a href="' . esc_url($student_link) . '">' . esc_html($student_name) . '</a></td>';
        echo '<td>' . esc_html(round($data->total_time, 2)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function mstimer_student_lesson_times($user_id, $course_id, $start_date, $end_date) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    try {
        $lessons_data = $wpdb->get_results($wpdb->prepare("
            SELECT lesson_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time
            FROM $table_name
            WHERE user_id = %d AND course_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY lesson_id
        ", $user_id, $course_id, $start_date, $end_date . ' 23:59:59'));
    } catch (Exception $e) {
        mstimer_log_error("Error fetching lessons for student: " . $e->getMessage());
        return;
    }

    echo '<h2>Lessons Times for Student</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Lesson</th><th>Total Time (seconds)</th></tr></thead><tbody>';

    foreach ($lessons_data as $data) {
        $lesson_title = get_the_title($data->lesson_id);
        echo '<tr>';
        echo '<td>' . esc_html($lesson_title) . '</td>';
        echo '<td>' . esc_html(round($data->total_time, 2)) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function mstimer_student_detail_page() {
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

    if ($user_id == 0 || $course_id == 0) {
        echo '<p>Invalid student or course.</p>';
        return;
    }

    $user_info = get_userdata($user_id);
    $student_name = $user_info ? $user_info->display_name : 'Unknown User';
    echo '<h1>Student: ' . esc_html($student_name) . '</h1>';

    // Date range filter
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="mstimer_student_detail">';
    echo '<input type="hidden" name="user_id" value="' . esc_attr($user_id) . '">';
    echo '<input type="hidden" name="course_id" value="' . esc_attr($course_id) . '">';
    echo 'Start Date: <input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
    echo 'End Date: <input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
    echo '<input type="submit" value="Filter" class="button">';
    echo '</form>';

    $export_url = add_query_arg([
        'export_csv' => '1',
        'page_type' => 'courses',
        'course_id'  => $course_id,
        'user_id'   => $user_id,    // Filter by a specific student/user
        'start_date' => $start_date,
        'end_date'   => $end_date,
    ]);
    echo '<a href="' . esc_url($export_url) . '" class="button">Export CSV</a>';
    

    mstimer_student_lesson_times($user_id, $course_id, $start_date, $end_date);
}


/**
 * Display students page content
 * 
 */
function mstimer_students_page() {
    global $wpdb;
    $course_table_name = $wpdb->prefix . 'mstimer_course_time_logs';
    $lesson_table_name = $wpdb->prefix . 'mstimer_lesson_time_logs';

    $students_data = $wpdb->get_results("SELECT user_id, course_id, SUM(time_spent) as total_time FROM $course_table_name GROUP BY user_id, course_id");

    echo '<h1>Students</h1>';
    echo '<table>';
    echo '<tr><th>Student</th><th>Course</th><th>Total Time (seconds)</th></tr>';

    foreach ($students_data as $data) {
        $user = get_userdata($data->user_id);
        $course_title = get_the_title($data->course_id);
        echo '<tr>';
        echo '<td><a href="?page=mstimer_students&user_id=' . $data->user_id . '">' . esc_html($user->display_name) . '</a></td>';
        echo '<td>' . esc_html($course_title) . '</td>';
        echo '<td>' . esc_html($data->total_time) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $lessons_data = $wpdb->get_results("SELECT lesson_id, SUM(time_spent) as total_time FROM $lesson_table_name WHERE user_id = $user_id GROUP BY lesson_id");

        echo '<h2>Lessons</h2>';
        echo '<table>';
        echo '<tr><th>Lesson</th><th>Total Time (seconds)</th></tr>';

        foreach ($lessons_data as $data) {
            $lesson_title = get_the_title($data->lesson_id);
            echo '<tr>';
            echo '<td>' . esc_html($lesson_title) . '</td>';
            echo '<td>' . esc_html($data->total_time) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}

/**
 * Export time tracking data to CSV
 */
function mstimer_export_csv() {
    if (!isset($_GET['export_csv'])) {
        return;
    }

    $page_type = isset($_GET['page_type']) ? sanitize_text_field($_GET['page_type']) : '';
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    $lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="export.csv"');

    $output = fopen('php://output', 'w');

    if ($page_type == 'courses') {
        $data = mstimer_get_course_data($start_date, $end_date);
        fputcsv($output, array('Course ID', 'Average Time (seconds)'));
        foreach ($data as $row) {
            fputcsv($output, array($row->course_id, round($row->avg_time, 2)));
        }
    } elseif ($page_type == 'lessons' && $course_id) {
        $data = mstimer_get_lessons_data($course_id, $start_date, $end_date);
        fputcsv($output, array('Lesson ID', 'Average Time (seconds)'));
        foreach ($data as $row) {
            fputcsv($output, array($row->lesson_id, round($row->avg_time, 2)));
        }
    } elseif ($page_type == 'lesson_detail' && $lesson_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mstimer_study_sessions';
        $students_data = $wpdb->get_results($wpdb->prepare("
            SELECT user_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time
            FROM $table_name
            WHERE lesson_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY user_id
        ", $lesson_id, $start_date, $end_date . ' 23:59:59'));
        fputcsv($output, array('User ID', 'Total Time (seconds)'));
        foreach ($students_data as $row) {
            fputcsv($output, array($row->user_id, round($row->total_time, 2)));
        }
    } elseif ($page_type == 'students' && $course_id) {
        $data = mstimer_get_students_data($course_id, $start_date, $end_date);
        fputcsv($output, array('User ID', 'Total Time (seconds)'));
        foreach ($data as $row) {
            fputcsv($output, array($row->user_id, round($row->total_time, 2)));
        }
    } elseif ($page_type == 'student_detail' && $user_id && $course_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mstimer_study_sessions';
        $lessons_data = $wpdb->get_results($wpdb->prepare("
            SELECT lesson_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time
            FROM $table_name
            WHERE user_id = %d AND course_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY lesson_id
        ", $user_id, $course_id, $start_date, $end_date . ' 23:59:59'));
        fputcsv($output, array('Lesson ID', 'Total Time (seconds)'));
        foreach ($lessons_data as $row) {
            fputcsv($output, array($row->lesson_id, round($row->total_time, 2)));
        }
    }

    fclose($output);
    exit;
}

add_action('admin_init', 'mstimer_export_csv');

/**
 * Save student time via AJAX
 */
function mstimer_save_student_time() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        wp_send_json_error("Table $table_name does not exist");
        return;
    }

    if (isset($_POST['user_id'], $_POST['course_id'], $_POST['lesson_id'], $_POST['start_time'], $_POST['end_time'], $_POST['session_date'])) {
        $result = $wpdb->insert($table_name, array(
            'user_id' => intval($_POST['user_id']),
            'course_id' => intval($_POST['course_id']),
            'lesson_id' => intval($_POST['lesson_id']),
            'start_time' => sanitize_text_field($_POST['start_time']),
            'end_time' => sanitize_text_field($_POST['end_time']),
            'session_date' => sanitize_text_field($_POST['session_date']),
        ));

        if ($result === false) {
            wp_send_json_error('Failed to insert data: ' . $wpdb->last_error);
        } else {
            wp_send_json_success('Data inserted successfully');
        }
    } else {
        wp_send_json_error('Invalid parameters.');
    }
}

add_action('wp_ajax_save_student_time', 'mstimer_save_student_time');
