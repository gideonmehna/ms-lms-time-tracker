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

/**
 * Enqueue scripts for frontend (time tracking)
 */
function mstimer_enqueue_scripts() {
    wp_enqueue_script('mstimer-js', plugins_url('/assets/js/mstimer.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('mstimer-js', 'mstimer_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'user_id' => get_current_user_id(),
        'course_id' => get_the_ID(),
    ));
}
add_action('wp_enqueue_scripts', 'mstimer_enqueue_scripts');

/**
 * Add admin menu and pages
 */
function mstimer_add_admin_menu() {
    add_menu_page('Time Tracking', 'Time Tracking', 'manage_options', 'mstimer_admin', 'mstimer_admin_page');
}
add_action('admin_menu', 'mstimer_add_admin_menu');
/**
 * Create database tables on plugin activation
 */
function mstimer_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Table for course time logs
    $course_table_name = $wpdb->prefix . 'mstimer_course_time_logs';
    $course_sql = "CREATE TABLE $course_table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        course_id BIGINT(20) NOT NULL,
        time_spent FLOAT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Table for lesson time logs
    $lesson_table_name = $wpdb->prefix . 'mstimer_lesson_time_logs';
    $lesson_sql = "CREATE TABLE $lesson_table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        course_id BIGINT(20) NOT NULL,
        lesson_id BIGINT(20) NOT NULL,
        time_spent FLOAT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($course_sql);
    dbDelta($lesson_sql);
}
register_activation_hook(__FILE__, 'mstimer_create_tables');
/**
 * Add admin menu and pages
 */
function mstimer_add_admin_menu() {
    add_menu_page('Time Tracking', 'Time Tracking', 'manage_options', 'mstimer_admin', 'mstimer_admin_page');
    add_submenu_page('mstimer_admin', 'Courses', 'Courses', 'manage_options', 'mstimer_courses', 'mstimer_courses_page');
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
 * Display courses page content
 */
function mstimer_courses_page() {
    global $wpdb;
    $course_table_name = $wpdb->prefix . 'mstimer_course_time_logs';
    $lesson_table_name = $wpdb->prefix . 'mstimer_lesson_time_logs';

    $courses_data = $wpdb->get_results("SELECT course_id, AVG(time_spent) as avg_time FROM $lesson_table_name GROUP BY course_id");

    echo '<h1>Courses</h1>';
    echo '<table>';
    echo '<tr><th>Course</th><th>Average Time (seconds)</th></tr>';

    foreach ($courses_data as $data) {
        $course_title = get_the_title($data->course_id);
        echo '<tr>';
        echo '<td><a href="?page=mstimer_courses&course_id=' . $data->course_id . '">' . esc_html($course_title) . '</a></td>';
        echo '<td>' . esc_html($data->avg_time) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    if (isset($_GET['course_id'])) {
        $course_id = intval($_GET['course_id']);
        $lessons_data = $wpdb->get_results("SELECT lesson_id, AVG(time_spent) as avg_time FROM $lesson_table_name WHERE course_id = $course_id GROUP BY lesson_id");

        echo '<h2>Lessons</h2>';
        echo '<table>';
        echo '<tr><th>Lesson</th><th>Average Time (seconds)</th></tr>';

        foreach ($lessons_data as $data) {
            $lesson_title = get_the_title($data->lesson_id);
            echo '<tr>';
            echo '<td>' . esc_html($lesson_title) . '</td>';
            echo '<td>' . esc_html($data->avg_time) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}

/**
 * Display students page content
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
    if (isset($_GET['export_csv'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mstimer_time_logs';

        $filename = 'course_time_logs_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename=' . $filename);

        $students_data = $wpdb->get_results("SELECT user_id, course_id, SUM(time_spent) as total_time FROM $table_name GROUP BY user_id, course_id");

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Student', 'Course', 'Total Time (seconds)'));

        foreach ($students_data as $data) {
            $user = get_userdata($data->user_id);
            $course_title = get_the_title($data->course_id);
            fputcsv($output, array($user->display_name, $course_title, $data->total_time));
        }

        fclose($output);
        exit;
    }
}
add_action('admin_init', 'mstimer_export_csv');

/**
 * Save student time via AJAX
 */
function mstimer_save_student_time() {
    if (isset($_POST['user_id'], $_POST['course_id'], $_POST['lesson_id'], $_POST['time_spent'])) {
        global $wpdb;

        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        $lesson_id = intval($_POST['lesson_id']);
        $time_spent = floatval($_POST['time_spent']);

        // Save time in the course table
        $course_table_name = $wpdb->prefix . 'mstimer_course_time_logs';
        $wpdb->insert($course_table_name, array(
            'user_id' => $user_id,
            'course_id' => $course_id,
            'time_spent' => $time_spent,
            'timestamp' => current_time('mysql'),
        ));

        // Save time in the lesson table
        $lesson_table_name = $wpdb->prefix . 'mstimer_lesson_time_logs';
        $wpdb->insert($lesson_table_name, array(
            'user_id' => $user_id,
            'course_id' => $course_id,
            'lesson_id' => $lesson_id,
            'time_spent' => $time_spent,
            'timestamp' => current_time('mysql'),
        ));

        wp_send_json_success();
    } else {
        wp_send_json_error('Invalid parameters.');
    }
}
add_action('wp_ajax_save_student_time', 'mstimer_save_student_time');
