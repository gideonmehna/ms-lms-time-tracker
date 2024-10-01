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
// function mstimer_add_admin_menu() {
//     add_menu_page('Time Tracking', 'Time Tracking', 'manage_options', 'mstimer_admin', 'mstimer_admin_page');
// }
// add_action('admin_menu', 'mstimer_add_admin_menu');

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
    dbDelta($sql);
}


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
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    // Date range filter
    $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days'));
    $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

    echo '<h1>Courses</h1>';
    echo '<form method="get">';
    echo '<input type="hidden" name="page" value="mstimer_courses">';
    echo 'Start Date: <input type="date" name="start_date" value="' . esc_attr($start_date) . '">';
    echo 'End Date: <input type="date" name="end_date" value="' . esc_attr($end_date) . '">';
    echo '<input type="submit" value="Filter">';
    echo '</form>';

    // Get course data
    $courses_data = $wpdb->get_results($wpdb->prepare("
        SELECT course_id, AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_time 
        FROM $table_name 
        WHERE start_time BETWEEN %s AND %s
        GROUP BY course_id
    ", $start_date, $end_date . ' 23:59:59'));

    echo '<table>';
    echo '<tr><th>Course</th><th>Average Time (seconds)</th></tr>';

    foreach ($courses_data as $data) {
        $course_title = get_the_title($data->course_id);
        echo '<tr>';
        echo '<td><a href="?page=mstimer_courses&course_id=' . $data->course_id . '&start_date=' . $start_date . '&end_date=' . $end_date . '">' . esc_html($course_title) . '</a></td>';
        echo '<td>' . esc_html($data->avg_time) . '</td>';
        echo '</tr>';
    }

    echo '</table>';

    if (isset($_GET['course_id'])) {
        $course_id = intval($_GET['course_id']);
        $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'lessons';

        echo '<h2>' . esc_html(get_the_title($course_id)) . '</h2>';
        echo '<h2 class="nav-tab-wrapper">';
        echo '<a href="?page=mstimer_courses&course_id=' . $course_id . '&tab=lessons&start_date=' . $start_date . '&end_date=' . $end_date . '" class="nav-tab ' . ($active_tab == 'lessons' ? 'nav-tab-active' : '') . '">Lessons</a>';
        echo '<a href="?page=mstimer_courses&course_id=' . $course_id . '&tab=students&start_date=' . $start_date . '&end_date=' . $end_date . '" class="nav-tab ' . ($active_tab == 'students' ? 'nav-tab-active' : '') . '">Students</a>';
        echo '</h2>';

        if ($active_tab == 'lessons') {
            $lessons_data = $wpdb->get_results($wpdb->prepare("
                SELECT lesson_id, AVG(TIMESTAMPDIFF(SECOND, start_time, end_time)) as avg_time 
                FROM $table_name 
                WHERE course_id = %d AND start_time BETWEEN %s AND %s
                GROUP BY lesson_id
            ", $course_id, $start_date, $end_date . ' 23:59:59'));

            echo '<table>';
            echo '<tr><th>Lesson</th><th>Average Time (seconds)</th></tr>';

            foreach ($lessons_data as $data) {
                $lesson_title = get_the_title($data->lesson_id);
                echo '<tr>';
                echo '<td><a href="?page=mstimer_courses&course_id=' . $course_id . '&lesson_id=' . $data->lesson_id . '&start_date=' . $start_date . '&end_date=' . $end_date . '">' . esc_html($lesson_title) . '</a></td>';
                echo '<td>' . esc_html($data->avg_time) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
        } elseif ($active_tab == 'students') {
            $students_data = $wpdb->get_results($wpdb->prepare("
                SELECT user_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time 
                FROM $table_name 
                WHERE course_id = %d AND start_time BETWEEN %s AND %s
                GROUP BY user_id
            ", $course_id, $start_date, $end_date . ' 23:59:59'));

            echo '<table>';
            echo '<tr><th>Student</th><th>Total Time (seconds)</th></tr>';

            foreach ($students_data as $data) {
                $user = get_userdata($data->user_id);
                echo '<tr>';
                echo '<td><a href="?page=mstimer_courses&course_id=' . $course_id . '&user_id=' . $data->user_id . '&start_date=' . $start_date . '&end_date=' . $end_date . '">' . esc_html($user->display_name) . '</a></td>';
                echo '<td>' . esc_html($data->total_time) . '</td>';
                echo '</tr>';
            }

            echo '</table>';
        }
    }

    if (isset($_GET['lesson_id'])) {
        $lesson_id = intval($_GET['lesson_id']);
        $students_data = $wpdb->get_results($wpdb->prepare("
            SELECT user_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time 
            FROM $table_name 
            WHERE lesson_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY user_id
        ", $lesson_id, $start_date, $end_date . ' 23:59:59'));

        echo '<h3>Students for ' . esc_html(get_the_title($lesson_id)) . '</h3>';
        echo '<table>';
        echo '<tr><th>Student</th><th>Total Time (seconds)</th></tr>';

        foreach ($students_data as $data) {
            $user = get_userdata($data->user_id);
            echo '<tr>';
            echo '<td>' . esc_html($user->display_name) . '</td>';
            echo '<td>' . esc_html($data->total_time) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        $course_id = intval($_GET['course_id']);
        $lessons_data = $wpdb->get_results($wpdb->prepare("
            SELECT lesson_id, SUM(TIMESTAMPDIFF(SECOND, start_time, end_time)) as total_time 
            FROM $table_name 
            WHERE user_id = %d AND course_id = %d AND start_time BETWEEN %s AND %s
            GROUP BY lesson_id
        ", $user_id, $course_id, $start_date, $end_date . ' 23:59:59'));

        $user = get_userdata($user_id);
        echo '<h3>Lessons for ' . esc_html($user->display_name) . '</h3>';
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
    if (isset($_POST['user_id'], $_POST['course_id'], $_POST['lesson_id'], $_POST['start_time'], $_POST['end_time'])) {
        global $wpdb;

        $user_id = intval($_POST['user_id']);
        $course_id = intval($_POST['course_id']);
        $lesson_id = intval($_POST['lesson_id']);
        // $time_spent = floatval($_POST['time_spent']);

        // Save time in the course table
        $table_name = $wpdb->prefix . 'mstimer_study_sessions';
        $wpdb->insert($table_name, array(
            'user_id' => intval($_POST['user_id']),
            'course_id' => intval($_POST['course_id']),
            'lesson_id' => intval($_POST['lesson_id']),
            'start_time' => sanitize_text_field($_POST['start_time']),
            'end_time' => sanitize_text_field($_POST['end_time']),
            'session_date' => sanitize_text_field($_POST['session_date']),
        ));

        
        wp_send_json_success();

    } else {
        wp_send_json_error('Invalid parameters.');
    }
}
add_action('wp_ajax_save_student_time', 'mstimer_save_student_time');
