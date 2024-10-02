<?php


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
 * Helper function to log errors
 */
function mstimer_log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($message);
    }
}