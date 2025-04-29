<?php
ini_set('max_execution_time', '300');

require_once("../../../wp-load.php");
define( 'DOING_AJAX', true );

send_origin_headers();

header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
header( 'X-Robots-Tag: noindex' );

// Check if action is provided
if ( empty( $_REQUEST['action'] ) || ! is_scalar( $_REQUEST['action'] ) ) {
    wp_send_json_error( 'No action specified', 400 );
}

require_once ABSPATH . 'wp-admin/includes/admin.php';
require_once ABSPATH . 'wp-admin/includes/ajax-actions.php';

send_nosniff_header();
nocache_headers();

// do_action( 'admin_init' );
$action = $_REQUEST['action'];

if ( is_user_logged_in() ) {
    
    if ( ! has_action( "wmg_ajax_{$action}" ) ) {
        wp_send_json_error( 'Invalid action: ' . $action, 400 );
    }

    // Execute the action - the action itself should send a JSON response
    do_action( "wmg_ajax_{$action}" );
    
    // If we get here, the action didn't send a response, so send a default one
    wp_send_json_error( 'No response from handler', 500 );
} else {
    wp_send_json_error( 'User not logged in', 401 );
}
