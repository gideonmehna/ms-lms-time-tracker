<?php


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
