<?php


/**
 * Enqueue scripts for frontend (time tracking)
 */
function mstimer_enqueue_scripts() {
    if (!is_page() && !is_single()) {
        wp_enqueue_script('mstimer-js', MSTIMER_PLUGIN_URL . 'assets/js/mstimer.js', array('jquery'), null, true);

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


