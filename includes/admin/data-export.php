<?php



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
