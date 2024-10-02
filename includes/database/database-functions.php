<?php



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


function mstimer_check_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';
    
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        mstimer_create_tables();
        error_log("mstimer_check_table: Table $table_name did not exist, attempted to create it");
    }
}

add_action('init', 'mstimer_check_table');


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
 * Clear all data from the mstimer_study_sessions table
 */
function mstimer_clear_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mstimer_study_sessions';

    // Delete all records from the table
    $wpdb->query("TRUNCATE TABLE $table_name");

    error_log("mstimer_clear_table: All data from table $table_name has been cleared.");
}


/**
 * Display the deactivation notice and option to clear database
 */
function mstimer_deactivation_notice() {
    if (get_transient('mstimer_deactivation_notice')) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><strong>Notice:</strong> You have deactivated the 'MSTimer' plugin.</p>
            <p>Would you like to clear all study session data from the database?</p>
            <form method="post">
                <input type="hidden" name="mstimer_clear_data_action" value="1">
                <button type="submit" class="button button-primary">Yes, clear the data</button>
                <button type="submit" class="button">No, keep the data</button>
            </form>
        </div>
        <?php
        // Clean up the transient after displaying it
        delete_transient('mstimer_deactivation_notice');
    }
}
add_action('admin_notices', 'mstimer_deactivation_notice');

/**
 * Handle the form submission for clearing data upon plugin deactivation
 */
function mstimer_handle_clear_data_request() {
    if (isset($_POST['mstimer_clear_data_action']) && $_POST['mstimer_clear_data_action'] == '1') {
        mstimer_clear_table();  // Clear the database table
        add_action('admin_notices', function () {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>All study session data has been successfully cleared from the database.</p>
            </div>
            <?php
        });
    }
}
add_action('admin_init', 'mstimer_handle_clear_data_request');
