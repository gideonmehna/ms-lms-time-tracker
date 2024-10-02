<?php


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
